<?php namespace de\thekid\dialog\actions;

use de\thekid\dialog\Storage;

class Album {

  public function __construct(private Storage $storage) { }

  <<get('/album/{name}')>>
  public function view(string $name) {
    return [
      'title' => 'Dialog',
      'album' => $this->storage->findAlbum($name),
    ];
  }
}