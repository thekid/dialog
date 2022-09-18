<?php namespace de\thekid\dialog\web;

use com\mongodb\Database;
use util\Date;
use web\Error;
use web\frontend\{Handler, Get};

#[Handler('/content')]
class Content {

  public function __construct(private Database $database) { }

  #[Get('/{id}')]
  public function index(string $id) {
    $entries= $this->database->collection('entries');
    $published= ['published' => ['$lt' => Date::now()]];

    $item= $entries->find(['slug' => ['$eq' => $id], ...$published]);
    if (!$item->present()) throw new Error(404);

    return [
      'item' => $item->first(),
      'text' => fn($node, $context, $options) => strip_tags($options[0]),
    ];
  }
}