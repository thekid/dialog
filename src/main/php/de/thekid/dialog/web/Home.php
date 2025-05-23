<?php namespace de\thekid\dialog\web;

use de\thekid\dialog\Repository;
use web\frontend\{Handler, Get, Head, View};

#[Handler('/')]
class Home {

  public function __construct(private Repository $repository) { }

  #[Get]
  public function index() {
    return View::named('home')->with([
      'cover'  => $this->repository->entry('@cover'),
      'newest' => $this->repository->newest(9),
    ]);
  }

  #[Get('/journeys')]
  public function journeys() {
    return View::named('journeys')->with([
      'journeys' => $this->repository->journeys(),
      'top'      => fn($node, $context, $options) => $options[1] <= $options[0],
    ]);
  }
}