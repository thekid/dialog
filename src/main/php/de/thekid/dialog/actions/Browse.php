<?php namespace de\thekid\dialog\actions;

use de\thekid\dialog\storage\Storage;
use util\data\Sequence;

class Browse {

  public function __construct(private Storage $storage) { }

  <<get('/')>>
  public function home() {
    return ['albums' => Sequence::of($this->storage->newestAlbums())->toArray()];
  }
}