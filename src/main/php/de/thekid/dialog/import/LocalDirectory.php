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
 */
class LocalDirectory extends Command {
  private $origin, $api;

  #[Arg(position: 0)]
  public function from(string $origin): void {
    $this->origin= new Folder($origin);
  }

  #[Arg(position: 1)]
  public function using(string $api): void {
    $this->api= new Endpoint($api);
  }

  /** Runs this command */
  public function run(): int {
    $publish= time();

    $source= Sources::in($this->origin);
    foreach ($source->items($this->origin) as $folder => $item) {
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