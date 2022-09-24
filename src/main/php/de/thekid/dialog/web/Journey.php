<?php namespace de\thekid\dialog\web;

use com\mongodb\Database;
use util\Date;
use web\Error;
use web\frontend\{Handler, Get};

#[Handler('/journey')]
class Journey {

  public function __construct(private Database $database) { }

  #[Get('/{id}')]
  public function index(string $id) {
    $entries= $this->database->collection('entries');
    $published= ['published' => ['$lt' => Date::now()]];

    $journey= $entries->find(['slug' => ['$eq' => $id], ...$published]);
    if (!$journey->present()) throw new Error(404);

    $items= $entries->aggregate([
      ['$match' => ['parent' => ['$eq' => $id], ...$published]],
      ['$sort'  => ['date' => -1]]
    ]);

    return [
      'journey'   => $journey->first(),
      'itinerary' => $items->all(),
      'scroll'    => fn($node, $context, $options) => substr($options[0], strlen($id) + 1),
      'text'      => fn($node, $context, $options) => strip_tags($options[0]),
    ];
  }
}