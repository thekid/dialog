<?php namespace de\thekid\dialog\api;

use de\thekid\dialog\Storage;
use util\Date;
use web\Error;

<<resource('/albums')>>
class Albums {

  public function __construct(private Storage $storage) { }

  <<get('/')>>
  public function all() {
    return $this->storage->allAlbums();
  }

  <<post('/')>>
  public function create(string $name, string $title, ?Date $created= null) {
    return $this->storage->createAlbum($name, $title, $created ?? Date::now());
  }

  <<get('/{name}')>>
  public function named(string $name) {
    if (null === ($album= $this->storage->findAlbum($name))) {
      throw new Error(404, 'No such album "'.$name.'"');
    }
    return $album;
  }

  <<delete('/{name}')>>
  public function remove(string $name) {
    return $this->storage->removeAlbum($name);
  }
}