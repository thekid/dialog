<?php namespace de\thekid\dialog\storage;

use rdbms\DBConnection;

interface Migration {

  public function perform(DBConnection $conn): iterable;
}