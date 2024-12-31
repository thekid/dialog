<?php namespace de\thekid\dialog\import;

use webservices\rest\Endpoint;

class FetchEntry extends Task {

  public function __construct(private string $slug) { }

  public function execute(Endpoint $api) {
    return $api->resource('entries/{0}?expand=$children', [$this->slug])
      ->put([])
      ->value()
    ;
  }

  public function description(): string { return "Fetching entry {$this->slug}"; }
}
