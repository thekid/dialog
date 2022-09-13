<?php namespace de\thekid\dialog\web;

use com\mongodb\Database;
use util\Date;
use web\frontend\{Handler, Get, Param};

#[Handler('/')]
class Browse {
  private $pagination= new Pagination(5);

  public function __construct(private Database $database) { }

  #[Get]
  public function listing(#[Param] $page= 1) {
    $entries= $this->database->collection('entries')->aggregate([
      ['$match' => ['parent' => ['$eq' => null], 'published' => ['$lt' => Date::now()]]],
      ['$sort'  => ['date' => -1]],
      ...$this->pagination->pipeline($page),
    ]);

    return $this->pagination->paginate($page, $entries);
  }
}