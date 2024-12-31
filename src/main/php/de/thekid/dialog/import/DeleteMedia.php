<?php namespace de\thekid\dialog\import;

use webservices\rest\Endpoint;

class DeleteMedia extends Task {

  public function __construct(private string $slug, private string $name) { }

  public function execute(Endpoint $api) {
    return $api->resource('entries/{0}/images/{1}', [$this->slug, $this->name])->delete();
  }

  /** @return string */
  public function description(): string { return "Deleting media {$this->slug}/{$this->name}"; }

}