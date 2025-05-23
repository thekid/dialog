<?php namespace de\thekid\dialog\import;

use de\thekid\dialog\processing\Processing;
use io\File;
use webservices\rest\{Endpoint, RestUpload, UnexpectedStatus};

class UploadMedia extends Task {

  public function __construct(private string $slug, private File $source, private Processing $processing) { }

  public function execute(Endpoint $api) {
    $resource= $api->resource('entries/{0}/images/{1}', [$this->slug, $this->source->filename]);
    $transfer= [];

    // Process targets first...
    foreach ($this->processing->targets($this->source) as $kind => $target) {
      $transfer[$kind]= $target;
      yield $kind => sprintf('%d kB', $target->size() / 1024);
    }

    // ...then perform upload as to not leave the connection open longer than necessary
    try {
      $upload= new RestUpload($api, $resource->request('PUT')->waiting(read: 3600));
      foreach ($this->processing->meta($this->source) as $name => $value) {
        $upload->pass('meta['.$name.']', $value);
      }
      foreach ($transfer as $kind => $target) {
        $upload->transfer($kind, $target->in(), $target->filename);
      }

      return $upload->finish()->value();
    } catch (UnexpectedStatus $e) {
      throw new CannotUpload($this->source->filename, $this->slug, $e->reason(), $e);
    }
  }

  /** @return string */
  public function description(): string {
    return "Uploading {$this->processing->kind()} media {$this->slug}/{$this->source->filename}";
  }
}