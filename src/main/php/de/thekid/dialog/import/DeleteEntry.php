<?php namespace de\thekid\dialog\import;

use webservices\rest\Endpoint;

class DeleteEntry extends Task {

  public function __construct(private string $slug) { }

  public function execute(Endpoint $api) {
    return $api->resource('entries/{0}', [$this->slug])->delete();
  }

  public function description(): string { return "Removing entry {$this->slug}"; }
}