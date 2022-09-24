<?php namespace de\thekid\dialog\web;

use de\thekid\dialog\Repository;
use web\Error;
use web\frontend\{Handler, Get};

#[Handler('/content')]
class Content {

  public function __construct(private Repository $repository) { }

  #[Get('/{id}')]
  public function index(string $id) {
    return [
      'item' => $this->repository->entry($id)->or(fn() => throw new Error(404, 'Not found: '.$id)),
      'text' => fn($node, $context, $options) => strip_tags($options[0]),
    ];
  }
}