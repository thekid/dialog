<?php namespace de\thekid\dialog\processing;

use com\mongodb\{MongoConnection, Error};
use de\thekid\dialog\Preferences;
use io\TempFile;
use io\streams\StreamTransfer;
use lang\IllegalStateException;
use util\Objects;
use util\cmd\{Arg, Command};
use web\Environment;
use webservices\rest\{Endpoint, RestUpload};

/**
 * Watch for changes on the `processing` collection and process media
 * accordingly.
 * 
 * @see   https://github.com/thekid/dialog/issues/44
 */
class Watch extends Command {
  private const WAIT_BEFORE_RETRYING= 10;
  private $api;

  /** Sets API endpoint to use */
  #[Arg(position: 0)]
  public function usingApi(?string $endpoint= null): void {
    $this->api= new Endpoint($endpoint ?? getenv('DIALOG_API'));
  }

  /** Runs forever, consuming the change stream */
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

    $preferences= new Preferences(new Environment('console'), 'config');
    $collection= new MongoConnection($preferences->get('mongo', 'uri'))
      ->database($preferences->optional('mongo', 'db', 'dialog'))
      ->collection('processing')
    ;

    process: $this->out->writeLine('> Processing ', $collection);
    $this->out->writeLine();
    try {
      Collections::watching($collection)->each(function($item) use($files) {
        $this->out->writeLinef(
          '  [%s %d %.3fkB] %s',
          date('r'),
          getmypid(),
          memory_get_usage() / 1024,
          Objects::stringOf($item, '  '),
        );

        if (null === ($processing= $files->processing($item['file']))) return;
        $this->out->write('  => Processing ', $processing->kind(), ' ', $item['file']);

        yield 'fetching' => $processing->kind();
        $resource= $this->api->resource('entries/{slug}/images/{file}', $item);
        $r= $resource->get();
        if (200 !== $r->status()) {
          throw new IllegalStateException($r->content());
        }

        // Fetch media into temporary file
        $source= new TempFile($item['file']);
        using ($s= new StreamTransfer($r->stream(), $source->out())) {
          $s->transferAll();
        }

        // Extract meta data from source, then convert source file to targets
        yield 'extracting' => $source->size();
        $meta= $processing->meta($source);

        $transfer= [];
        foreach ($processing->targets($source, filename: $item['file']) as $kind => $target) {
          yield 'targeting' => $kind;
          $transfer[$kind]= $target;
        }

        // Upload processed results and meta data
        $size= sizeof($transfer);
        yield 'uploading' => $size;
        $upload= new RestUpload($this->api, $resource->request('PUT')->waiting(read: 3600));
        foreach ($meta as $key => $value) {
          $upload->pass('meta['.$key.']', $value);
        }
        foreach ($transfer as $kind => $file) {
          $upload->transfer($kind, $file->in(), $file->filename);
        }
        $r= $upload->finish();
        $this->out->writeLine(': ', $r->status());

        yield 'finished' => $size;
        $source->unlink();
      });
    } catch (Error $e) {
      $this->err->writeLine('# Gracefully handling ', $e);
      sleep(self::WAIT_BEFORE_RETRYING);
      goto process;
    }
    return 0;
  }
}