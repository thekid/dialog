<?php namespace de\thekid\dialog\web;

use de\thekid\dialog\Repository;
use web\frontend\{Handler, Get};

#[Handler('/')]
class Home {

  public function __construct(private Repository $repository) { }

  #[Get]
  public function index() {
    return [
      'cover'  => $this->repository->entry('@cover'),
      'newest' => $this->repository->newest(3),
      'text'   => fn($node, $context, $options) => strip_tags($options[0]),
    ];
  }
}