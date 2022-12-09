<?php namespace de\thekid\dialog;

use io\Folder;
use web\handler\FilesFrom;

/** @test de.thekid.dialog.unittest.StorageTest */
class Storage extends FilesFrom {

  /** Returns folder for a given entry */
  public function folder(string... $args): Folder {
    return new Folder($this->path(), 'image', ...$args);
  }
}