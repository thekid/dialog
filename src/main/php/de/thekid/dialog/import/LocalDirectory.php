<?php namespace de\thekid\dialog\import;

use de\thekid\dialog\import\Sources;
use img\Image;
use img\io\{StreamReader, WebpStreamWriter};
use io\streams\TextReader;
use io\{Folder, File};
use lang\{Enum, IllegalArgumentException, FormatException};
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

  /** Runs this command */
  public function run(): int {
    $publish= time();

    foreach (Sources::in($this->origin) as $folder => $item) {
      $this->out->writeLine('[+] ', $item);
      $r= $this->api->resource('entries/{0}', [$item['slug']])->put($item, 'application/json');
      $this->out->writeLine(' => ', $r->value());

      foreach ($folder->entries() as $entry) {
        if (preg_match('/^(?!(thumb|full)-).+(.jpg|.jpeg|.png|.webp)$/i', $entry->name())) {
          $this->out->write(' => Processing ', $entry->name());

          $transfer= [];
          foreach (['thumb' => 1024, 'full' => 3840] as $kind => $size) {
            $source= $entry->asFile();
            $webp= new File($folder, $kind.'-'.$entry->name().'.webp');
            if (!$webp->exists() || $webp->lastModified() < $source->lastModified()) {
              $image= Image::loadFrom(new StreamReader($source));
              $resized= Image::create($size, (int)($image->height * ($size / $image->width)), Image::TRUECOLOR);
              $resized->resampleFrom($image);
              $resized->saveTo(new WebpStreamWriter($webp));
              $transfer[$kind]= $webp;
            }
          }

          if (empty($transfer)) {
            $this->out->writeLine(': (not updated)');
          } else {
            $upload= $this->api->resource('entries/{0}/images/{1}', [$item['slug'], $entry->name()])->upload('PUT');
            foreach ($transfer as $kind => $file) {
              $upload->transfer($file->filename, $file->in(), $kind);
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