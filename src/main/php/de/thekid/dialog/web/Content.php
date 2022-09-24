<?php namespace de\thekid\dialog\web;

use de\thekid\dialog\Repository;
use web\Error;
use web\frontend\{Handler, Get};

#[Handler('/content')]
class Content {

  public function __construct(private Repository $repository) { }

  #[Get('/{id}')]
  public function index(string $id) {
    $entry= $this->repository->entry($id);
    $entry->present() || throw new Error(404, 'Not found: '.$id);

    return [
      'item' => $entry->first(),
      'text' => fn($node, $context, $options) => strip_tags($options[0]),
    ];
  }
}