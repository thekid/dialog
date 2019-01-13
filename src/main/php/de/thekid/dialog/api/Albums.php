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
    if (!preg_match('/^[a-z0-9_-]+$/', $name)) {
      throw new Error(400, 'Album name must consist of letters, numbers, dashes and underscores only');
    }

    if (null !== $this->storage->findAlbum($name)) {
      throw new Error(400, 'Album "'.$name.'" already exists');
    }

    $this->storage->createAlbum($name, $title, $created ?? Date::now());
  }

  <<get('/{name}')>>
  public function named(string $name) {
    if (null === ($album= $this->storage->findAlbum($name))) {
      throw new Error(404, 'No such album "'.$name.'"');
    }

    return $album;
  }

  <<put('/{name}')>>
  public function update(string $name, string $title, ?Date $created= null) {
    if (null === $this->storage->findAlbum($name)) {
      throw new Error(400, 'No such album "'.$name.'"');
    }

    $this->storage->updateAlbum($name, $title, $created ?? Date::now());
  }

  <<delete('/{name}')>>
  public function remove(string $name) {
    if (null === $this->storage->findAlbum($name)) {
      throw new Error(400, 'No such album "'.$name.'"');
    }

    $this->storage->removeAlbum($name);
  }
}