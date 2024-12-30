<?php namespace de\thekid\dialog\import;

use util\Objects;
use webservices\rest\Endpoint;

class PublishEntry extends Task {

  public function __construct(private string $slug, private array<string, mixed> $changes) { }

  public function execute(Endpoint $api) {
    return $api->resource('entries/{0}', [$this->slug])->patch($this->changes, 'application/json');
  }

  public function description(): string { return "Publishing entry {$this->slug}"; }
}