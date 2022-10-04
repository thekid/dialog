<?php namespace de\thekid\dialog\web;

use de\thekid\dialog\Repository;
use web\frontend\{Handler, Get, View};

#[Handler('/')]
class Home {

  public function __construct(private Repository $repository) { }

  #[Get]
  public function index() {
    return View::named('home')->with([
      'cover'  => $this->repository->entry('@cover'),
      'newest' => $this->repository->newest(6),
    ]);
  }

  #[Get('/journeys')]
  public function journeys() {
    return View::named('journeys')->with([
      'journeys' => $this->repository->journeys(),
      'range'    => fn($node, $context, $options) => {
        $from= date($options['format'], strtotime($options[0]));
        $until= date($options['format'], strtotime($options[1]));
        return $from === $until ? $from : $from.' - '.$until;
      }
    ]);
  }
}