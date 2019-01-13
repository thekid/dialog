<?php namespace de\thekid\dialog\api;

use de\thekid\dialog\Storage;

<<resource('/configuration')>>
class Configuration {

  public function __construct(private Storage $storage) { }

  <<get('/')>>
  public function values(): array<string, string> {
    return $this->storage->configuration();
  }

  <<put('/')>>
  public function update(<<entity>> array<string, string> $values): array<string, string> {
    $this->storage->configure($values);
    return $this->storage->configuration();
  }
}