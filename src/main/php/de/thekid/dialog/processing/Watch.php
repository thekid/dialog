<?php namespace de\thekid\dialog\processing;

use com\mongodb\MongoConnection;
use de\thekid\dialog\Preferences;
use io\TempFile;
use io\streams\StreamTransfer;
use util\Objects;
use util\cmd\{Arg, Command};
use web\Environment;
use webservices\rest\Endpoint;

class Watch extends Command {
  private $api;

  #[Arg(position: 0)]
  public function usingApi(string $endpoint): void {
    $this->api= new Endpoint($endpoint);
  }

  /** Runs forever, consuming the change stream */
  public function run(): int {
    $images= new Images()
      ->targeting('preview', new ResizeTo(720, 'jpg'))
      ->targeting('thumb', new ResizeTo(1024, 'webp'))
      ->targeting('full', new ResizeTo(3840, 'webp'))
    ;
    $videos= new Videos()
      ->targeting('preview', new ResizeTo(720, 'jpg'))
      ->targeting('thumb', new ResizeTo(1024, 'webp'))
    ;

    $preferences= new Preferences(new Environment('console'), 'config');
    $collection= new MongoConnection($preferences->get('mongo', 'uri'))
      ->database($preferences->optional('mongo', 'db', 'dialog'))
      ->collection('processing')
    ;

    $this->out->writeLine('> Processing ', $collection);
    $this->out->writeLine();
    Collection::watch($collection)->each(function($item) use($images, $videos) {
      $this->out->writeLinef(
        '  [%s %d %.3fkB] %s',
        date('r'),
        getmypid(),
        memory_get_usage() / 1024,
        Objects::toString($item, '  '),
      );

      if (preg_match('/(.jpg|.jpeg|.png|.webp)$/i', $item['file'])) {
        $this->out->write('  => Processing image ', $item['file']);
        $processing= $images;
      } else if (preg_match('/(.mp4|.mpeg|.mov)$/i', $item['file'])) {
        $this->out->write('  => Processing video ', $item['file']);
        $processing= $video;
      } else {
        return;
      }

      $resource= $this->api->resource('entries/{slug}/images/{file}', $item);

      // Fetch media into temporary file
      $source= new TempFile($item['file']);
      using ($s= new StreamTransfer($resource->get()->stream(), $source->out())) {
        $s->transferAll();
      }

      // Extract meta data from source
      $meta= $processing->meta($source);

      // Create target files by converting source
      $transfer= [];
      foreach ($processing->targets($source) as $kind => $target) {
        $transfer[$kind]= $target;
      }

      // Upload processed results and meta data
      $upload= new RestUpload($this->api, $resource->request('PUT')->waiting(read: 3600));
      foreach ($meta as $key => $value) {
        $upload->pass('meta['.$key.']', $value);
      }
      foreach ($transfer as $kind => $file) {
        $upload->transfer($kind, $file->in(), $file->filename);
      }
      $r= $upload->finish();
      $this->out->writeLine(': ', $r->status());
    });
    return 0;
  }
}