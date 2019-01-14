<?php namespace de\thekid\dialog;

use rdbms\DBConnection;

interface Migration {

  public function perform(DBConnection $conn): iterable;
}