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
    $item= $this->database->collection('entries')->find([
      'slug'      => ['$eq' => $id],
      'published' => ['$lt' => Date::now()]
    ]);
    if (!$item->present()) throw new Error(404, 'Not found: '.$id);

    return [
      'item' => $item->first(),
      'text' => fn($node, $context, $options) => strip_tags($options[0]),
    ];
  }
}