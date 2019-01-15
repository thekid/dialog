<?php namespace de\thekid\dialog\actions;

use de\thekid\dialog\storage\Storage;

class Album {

  public function __construct(private Storage $storage) { }

  <<get('/album/{name}')>>
  public function view(string $name) {
    return ['album' => $this->storage->findAlbum($name)];
  }
}