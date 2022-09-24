<?php namespace de\thekid\dialog\web;

use de\thekid\dialog\Repository;
use web\Error;
use web\frontend\{Handler, Get};

#[Handler('/journey')]
class Journey {

  public function __construct(private Repository $repository) { }

  #[Get('/{id}')]
  public function index(string $id) {
    $journey= $this->repository->entry($id) ?? throw new Error(404, 'Not found: '.$id);
    return [
      'journey'   => $journey,
      'itinerary' => $this->repository->children($id),
      'scroll'    => fn($node, $context, $options) => substr($options[0], strlen($id) + 1),
      'text'      => fn($node, $context, $options) => strip_tags($options[0]),
    ];
  }
}