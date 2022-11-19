<?php namespace de\thekid\dialog;

use io\Folder;
use web\handler\FilesFrom;

class Storage extends FilesFrom {

  /** Returns folder for a given entry */
  public function folder(string $entry): Folder {
    return new Folder($this->path(), 'image', $entry);
  }
}