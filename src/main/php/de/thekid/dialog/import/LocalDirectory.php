<?php namespace de\thekid\dialog\import;

use de\thekid\dialog\import\Sources;
use img\Image;
use img\io\{StreamReader, WebpStreamWriter};
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
  public function setVerbose() {
    $this->api->setTrace(Logging::all()->toConsole());
  }

  /** Returns image resizing targets */
  private function targets(): iterable {
    yield 'preview' => new ResizeTo(720, 'jpg');
    yield 'thumb'   => new ResizeTo(1024, 'webp');
    yield 'full'    => new ResizeTo(3840, 'webp');
  }

  /** Executes a given external command and returns its exit code */
  private function execute(string $command, array<string> $args): void {
    $p= new Process($command, $args, null, null, [STDIN, STDOUT, STDERR]);
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

      $entry= $this->api->resource('entries/{0}', [$item['slug']])->put($item, 'application/json')->value();
      $this->out->writeLine(' => ID<', $entry['_id'], '>');

      foreach ($folder->entries() as $entry) {
        if (preg_match('/^(thumb|preview|full|video)-/', $entry->name())) continue;

        $transfer= [];
        if (preg_match('/(.jpg|.jpeg|.png|.webp)$/i', $entry->name())) {
          $source= $entry->asFile();
          $this->out->write(' => Processing image ', $entry->name());

          // Resize images
          foreach ($this->targets() as $kind => $target) {
            if ($file= $target->resize($source, $kind, $source->filename)) {
              $transfer[$kind]= $file;
            }
          }
        } else if (preg_match('/(.mp4|.mpeg|.mov)$/i', $entry->name())) {
          $source= $entry->asFile();
          $this->out->write(' => Processing video ', $entry->name());

          // Convert to web-optimized H.264 video, scaling to a width of 1920 pixels
          $video= new File($folder, 'video-'.$entry->name().'.mp4');
          if (!$video->exists() || $video->lastModified() < $source->lastModified()) {
            $this->execute('ffmpeg', [
              '-i', (string)$entry,
              '-vcodec', 'libx264',
              '-vf', 'scale=1920:-1',
              '-acodec', 'aac',
              '-g', '30', // group of picture (GOP)
              $video->getURI(),
            ]);
            // $transfer['video']= $video;

            // Uploading files that take longer than ~30 seconds is, for some reason,
            // broken, and will result in a) the import tool crashing and b) the server
            // to end up in an endless blocking loop.
            $headers= [];
            foreach ($this->api->headers() as $name => $value) {
              $headers[]= '-H';
              $headers[]= $name.': '.$value;
            }
            $this->execute('curl', [
              '-v', ...$headers,
              '-X', 'PUT',
              '-F', 'video=@'.strtr($video->getURI(), [DIRECTORY_SEPARATOR => '/']),
              $this->api->resource('entries/{0}/images/{1}', [$item['slug'], $entry->name()])->uri(),
            ]);
          }

          // Extract screenshot and preview image
          $thumb= new File($folder, 'thumb-'.$entry->name().'.webp');
          if (!$thumb->exists() || $thumb->lastModified() < $source->lastModified()) {

            // Screenshot to JPEG using ffmpeg
            $screen= new File($folder, sha1($entry->name()).'.jpg');
            $this->execute('ffmpeg', [
              '-i', (string)$entry,
              '-ss', '00:00:03',
              '-vsync', 'vfr',
              '-frames:v', '1',
              '-q:v', '1',
              '-qscale:v', '1',
              $screen->getURI(),
            ]);

            // Convert and resize this JPEG
            foreach (['preview' => new ResizeTo(720, 'jpg'), 'thumb' => new ResizeTo(1024, 'webp')] as $kind => $target) {
              if ($file= $target->resize($screen, $kind, $source->filename)) {
                $transfer[$kind]= $file;
              }
            }
            $screen->unlink();
          }
        } else {
          continue;
        }

        if (empty($transfer)) {
          $this->out->writeLine(': (not updated)');
        } else {
          $upload= new RestUpload($this->api, $this->api
            ->resource('entries/{0}/images/{1}', [$item['slug'], $entry->name()])
            ->request('PUT')
            ->waiting(read: 3600)
          );
          foreach ($transfer as $kind => $file) {
            $upload->transfer($kind, $file->in(), $file->filename);
          }
          $r= $upload->finish();
          $this->out->writeLine(': ', $r->status());
        }
      }

      $r= $this->api->resource('entries/{0}/published', [$item['slug']])->put($publish, 'application/json');
      $this->out->writeLine(' => ', $r->value());
    }
    return 0;
  }
}