<?php namespace de\thekid\dialog\import;

use de\thekid\dialog\import\Sources;
use img\Image;
use img\io\{StreamReader, WebpStreamWriter};
use io\streams\TextReader;
use io\{Folder, File};
use lang\{Enum, IllegalArgumentException, FormatException};
use peer\http\HttpConnection;
use util\Date;
use util\cmd\{Command, Arg};
use webservices\rest\Endpoint;

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

  /** Returns targets */
  private function targets(): iterable {
    yield 'preview' => new ResizeTo(720, 'jpg');
    yield 'thumb'   => new ResizeTo(1024, 'webp');
    yield 'full'    => new ResizeTo(3840, 'webp');
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

      $r= $this->api->resource('entries/{0}', [$item['slug']])->put($item, 'application/json');
      $this->out->writeLine(' => ', $r->value());

      foreach ($folder->entries() as $entry) {
        if (preg_match('/^(?!(thumb|full|preview)-).+(.jpg|.jpeg|.png|.webp)$/i', $entry->name())) {
          $this->out->write(' => Processing ', $entry->name());

          $transfer= [];
          $source= $entry->asFile();
          foreach ($this->targets() as $kind => $target) {
            if ($file= $target->resize($source, $kind)) {
              $transfer[$kind]= $file;
            }
          }

          if (empty($transfer)) {
            $this->out->writeLine(': (not updated)');
          } else {
            $upload= $this->api->resource('entries/{0}/images/{1}', [$item['slug'], $entry->name()])->upload('PUT');
            foreach ($transfer as $kind => $file) {
              $upload->transfer($kind, $file->in(), $file->filename);
            }
            $r= $upload->finish();
            $this->out->writeLine(': ', $r->status());
          }
        }
      }

      $r= $this->api->resource('entries/{0}/published', [$item['slug']])->put($publish, 'application/json');
      $this->out->writeLine(' => ', $r->value());
    }
    return 0;
  }
}