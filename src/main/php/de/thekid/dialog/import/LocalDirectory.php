<?php namespace de\thekid\dialog\import;

use de\thekid\dialog\processing\{Files, Images, Videos, ResizeTo};
use io\{Folder, File};
use lang\{IllegalArgumentException, IllegalStateException, FormatException, Process};
use peer\http\HttpConnection;
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
 * - cover.md: The image to use for the cover page
 */
class LocalDirectory extends Command {
  private $origin, $api;
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

  /** Transfers images even if they have not been changed */
  #[Arg]
  public function setForce() {
    $this->force= true;
  }

  /** Add verbose logging for API calls */
  #[Arg]
  public function setVerbose() {
    $this->api->setTrace(Logging::all()->toConsole());
  }

  /** Executes a given external command and returns its exit code */
  private function execute(string $command, array<string> $args, $redirect= null): void {
    $p= new Process($command, $args, null, null, [STDIN, $redirect ?? STDOUT, STDERR]);
    if (0 === ($r= $p->close())) return;

    throw new IllegalStateException($p->getCommandLine().' exited with exit code '.$r);
  }

  /** Runs this command */
  public function run(): int {
    $files= new Files()
      ->matching(['.jpg', '.jpeg', '.png', '.webp'], new Images()
        ->targeting('preview', new ResizeTo(720, 'jpg'))
        ->targeting('thumb', new ResizeTo(1024, 'webp'))
        ->targeting('full', new ResizeTo(3840, 'webp'))
      )
      ->matching(['.mp4', '.mpeg', '.mov'], new Videos()
        ->targeting('preview', new ResizeTo(720, 'jpg'))
        ->targeting('thumb', new ResizeTo(1024, 'webp'))
      )
    ;

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
      $media= [];
      foreach ($document['images'] ?? [] as $image) {
        $media[$image['name']]= $image;
      }

      foreach ($folder->entries() as $entry) {
        $name= $entry->name();
        if (!$entry->isFile() || preg_match('/^(thumb|preview|full|video|screen)-/', $name)) continue;

        // Select processing method
        if (null === ($processing= $files->processing($name))) continue;
        $source= $entry->asFile();

        // Synchronize with server
        $modified= $media[$name]['modified'] ?? null;
        if ($this->force || null === $modified || $source->lastModified() > $modified) {
          $resource= $this->api->resource('entries/{0}/images/{1}', [$item['slug'], $entry->name()]);
          $transfer= [];
          foreach ($processing->targets($source) as $kind => $target) {

            // FIXME: Uploading files that take longer than ~30 seconds is, for some reason,
            // broken, and will result in a) the import tool crashing and b) the server ending
            // up in an endless blocking loop. Use `curl` for videos instead.
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
          foreach ($processing->meta($source) as $key => $value) {
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
        unset($media[$name]);
      }

      // Clean up images
      foreach ($media as $name => $_) {
        $r= $this->api->resource('entries/{0}/images/{1}', [$item['slug'], $name])->delete();
        $this->out->writeLine(' => Deleted ', $r->value(), ' from ', $item['slug']);
      }

      $r= $this->api->resource('entries/{0}/published', [$item['slug']])->put($publish, 'application/json');
      $this->out->writeLine('# ', $r->value());
    }
    return 0;
  }
}