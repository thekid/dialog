<?php namespace de\thekid\dialog\storage;

use rdbms\DBConnection;

class CopyOf implements Migration {

  public function __construct(private $origin, private $target) { }

  public function perform(DBConnection $conn): iterable {
    $conn->close();

    $cwd= getcwd();
    yield 'Copying '.$this->origin->relativeTo($cwd).' to '.$this->target->relativeTo($cwd);
    copy($this->origin, $this->target);

    yield 'Reconnecting';
    $conn->connect();
  }
}