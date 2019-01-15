<?php namespace de\thekid\dialog\storage;

use rdbms\DBConnection;

class CopyOf implements Migration {

  public function __construct(
    private Storage $storage,
    private $origin,
    private $target
  ) { }

  public function perform(): iterable {
    $this->storage->connection()->close();

    $cwd= getcwd();
    yield 'Copying '.$this->origin->relativeTo($cwd).' to '.$this->target->relativeTo($cwd);
    copy($this->origin, $this->target);

    yield 'Reconnecting';
    $this->storage->connection()->connect();
  }
}