<?php namespace de\thekid\dialog\import;

use de\thekid\dialog\import\Sources;
use img\Image;
use img\io\{StreamReader, WebpStreamWriter, MetaDataReader};
use io\streams\TextReader;
use io\{Folder, File};
use lang\{Enum, IllegalArgumentException, IllegalStateException, FormatException, Process};
use peer\http\HttpConnection;
use util\Date;
use util\cmd\{Command, Arg};
use util\log\Logging;
use webservices\rest\{Endpoint, RestUpload};

/**
 * Imports items from a local directory.
 *
 * The item type is determined by the presence of one of these files
 * in the given origin folder.
 *
 * - content.md: A simple content element
 * - journey.md: A journey element containt content elements
 */
class LocalDirectory extends Command {
  private $origin, $api;
  private $meta= new MetaDataReader();
  private $force= false;

  /** Sets origin folder, e.g. `./imports/album` */
  #[Arg(position: 0)]
  public function from(string $origin): void {
    $this->origin= new Folder($origin);
  }

  /** Sets API url, e.g. `http://user:pass@localhost:8080/api` */
  #[Arg(position: 1)]
  public function using(string $api): void {
    $this->api= new Endpoint($api);
  }

  #[Arg]
  public function setForce() {
    $this->force= true;
  }

  #[Arg]
  public function setVerbose() {
    $this->api->setTrace(Logging::all()->toConsole());
  }

  /** Returns image resizing targets */
  private function imageFile(File $source): iterable {
    foreach ([
      'preview' => new ResizeTo(720, 'jpg'),
      'thumb'   => new ResizeTo(1024, 'webp'),
      'full'    => new ResizeTo(3840, 'webp')
    ] as $kind => $target) {
      yield $kind => $target->resize($source, $kind, $source->filename);
    }
  }

  /** Returns image meta data */
  private function imageMeta(File $source): iterable {
    try {
      $meta= $this->meta->read($source->in());
      if ($exif= $meta?->exifData()) {
        yield 'width' => $exif->width;
        yield 'height' => $exif->height;
        yield 'dateTime' => $exif->dateTime?->toString('c');
        yield 'make' => $exif->make;
        yield 'model' => $exif->model;
        yield 'apertureFNumber' => $exif->apertureFNumber;
        yield 'exposureTime' => $exif->exposureTime;
        yield 'isoSpeedRatings' => $exif->isoSpeedRatings;
        yield 'focalLength' => $exif->focalLength;
        yield 'flashUsed' => $exif->flashUsed();
      }
    } catch ($e) {
      $this->err->writeLine('Cannot extract meta data from ', $source, ': ', $e);
    } finally {
      $source->close();
    }
  }

  /** Returns video resizing targets */
  private function videoFile(File $source): iterable {

    // 1. Convert to web-optimized H.264 video, scaling to a width of 1920 pixels
    $video= new File($source->path, 'video-'.$source->filename.'.mp4');
    if (!$video->exists() || $source->lastModified() > $video->lastModified()) {
      $this->execute('ffmpeg', [
        '-y',       // Overwrite files without asking
        '-i', $source->getURI(),
        '-vcodec', 'libx264',
        '-vf', 'scale=1920:-1',
        '-acodec', 'aac',
        '-g', '30', // Group of picture (GOP)
        $video->getURI(),
      ]);
    }
    yield 'video' => $video;

    // 2. Extract screenshot and preview image using ffmpeg
    $screen= new File($source->path, 'screen-'.$source->filename.'.jpg');
    if (!$screen->exists() || $source->lastModified() > $screen->lastModified()) {
      $this->execute('ffmpeg', [
        '-y',
        '-i', $source->getURI(),
        '-ss', '00:00:03',
        '-vsync', 'vfr',
        '-frames:v', '1',
        '-q:v', '1',
        '-qscale:v', '1',
        $screen->getURI(),
      ]);
    }

    // 3. Convert and resize screenshot JPEG
    foreach ([
      'preview' => new ResizeTo(720, 'jpg'),
      'thumb'   => new ResizeTo(1024, 'webp')
    ] as $kind => $target) {
      yield $kind => $target->resize($screen, $kind, $source->filename);
    }
  }

  /** Executes a given external command and returns its exit code */
  private function execute(string $command, array<string> $args, $redirect= null): void {
    $p= new Process($command, $args, null, null, [STDIN, $redirect ?? STDOUT, STDERR]);
    if (0 === ($r= $p->close())) return;

    throw new IllegalStateException($p->getCommandLine().' exited with exit code '.$r);
  }

  /** Runs this command */
  public function run(): int {
    $publish= time();

    foreach (Sources::in($this->origin) as $folder => $item) {
      $this->out->writeLine('[+] ', $item);

      // Aggregate coordinates from Google Maps links
      foreach ($item['locations'] as &$location) {
        $r= new HttpConnection($location['link'])->get();
        if (!preg_match('#/maps/place/[^/]+/@([0-9.-]+),([0-9.-]+),([0-9.]+)z#', $r->header('Location')[0], $m)) {
          throw new FormatException('Cannot resolve '.$location['link'].': '.$r->toString());
        }

        $location['lat']= (float)$m[1];
        $location['lon']= (float)$m[2];
        $location['zoom']= (float)$m[3];
      }

      // Fetch existing entry
      $document= $this->api->resource('entries/{0}', [$item['slug']])->put($item, 'application/json')->value();
      $this->out->writeLine(' => ID<', $document['_id'], '>');
      $images= [];
      foreach ($document['images'] ?? [] as $image) {
        $images[$image['name']]= $image;
      }

      foreach ($folder->entries() as $entry) {
        $name= $entry->name();
        if (!$entry->isFile() || preg_match('/^(thumb|preview|full|video|screen)-/', $name)) continue;

        // Select processing method
        $source= $entry->asFile();
        if (preg_match('/(.jpg|.jpeg|.png|.webp)$/i', $name)) {
          $this->out->write(' => Processing image ', $name);
          $targets= $this->imageFile(...);
          $meta= $this->imageMeta(...);
        } else if (preg_match('/(.mp4|.mpeg|.mov)$/i', $name)) {
          $this->out->write(' => Processing video ', $name);
          $targets= $this->videoFile(...);
          $meta= fn() => [];
        } else {
          continue;
        }

        // Synchronize with server
        $modified= $images[$name]['modified'] ?? null;
        if ($this->force || null === $modified || $source->lastModified() > $modified) {
          $resource= $this->api->resource('entries/{0}/images/{1}', [$item['slug'], $entry->name()]);
          $transfer= [];
          foreach ($targets($source) as $kind => $target) {

            // FIXME: Uploading files that take longer than ~30 seconds is, for some reason,
            // broken, and will result in a) the import tool crashing and b) the server to
            // end up in an endless blocking loop. Use `curl` for videos instead.
            if ('video' === $kind) {
              $this->execute('curl', [
                '-#',
                '-X', 'PUT',
                '-H', 'Authorization: '.$this->api->headers()['Authorization'],
                '-F', $kind.'=@'.strtr($target->getURI(), [DIRECTORY_SEPARATOR => '/']),
                $resource->uri(),
              ], ['null']);
            } else {
              $transfer[$kind]= $target;
            }
          }

          $upload= new RestUpload($this->api, $resource->request('PUT')->waiting(read: 3600));
          foreach ($meta($source) as $key => $value) {
            $upload->pass('meta['.$key.']', $value);
          }
          foreach ($transfer as $kind => $file) {
            $upload->transfer($kind, $file->in(), $file->filename);
          }
          $r= $upload->finish();
          $this->out->writeLine(': ', $r->status());
        } else {
          $this->out->writeLine(': (not updated)');
        }

        // Mark image as processed
        unset($images[$name]);
      }

      // Clean up images
      foreach ($images as $name => $image) {
        $r= $this->api->resource('entries/{0}/images/{1}', [$item['slug'], $name])->delete();
        $this->out->writeLine(' => Deleted ', $r->value(), ' from ', $item['slug']);
      }

      $r= $this->api->resource('entries/{0}/published', [$item['slug']])->put($publish, 'application/json');
      $this->out->writeLine('# ', $r->value());
    }
    return 0;
  }
}