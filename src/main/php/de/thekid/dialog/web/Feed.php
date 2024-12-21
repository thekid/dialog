<?php namespace de\thekid\dialog\web;

use de\thekid\dialog\{Repository, Pagination};
use util\Date;
use web\Headers;
use web\frontend\{Handler, Header, Get, Param, View};

#[Handler('/feed')]
class Feed {
  private $pagination= new Pagination(8);

  public function __construct(private Repository $repository) { }

  #[Get]
  public function listing(#[Param] $page= 1) {
    return $this->repository->entries($this->pagination, (int)$page);
  }

  #[Get('/atom')]
  public function atom(#[Header('If-Modified-Since')] $since= null) {
    $items= $this->repository->newest(20);
    if ($since && $items && !$items[0]['date']->isAfter(new Date($since))) {
      $view= View::empty()->status(304);
    } else {
      $view= View::named('atom')->with(['items' => $items]);
    }

    return $view
      ->header('Cache-Control', 'max-age=3600')
      ->header('Content-Type', 'application/atom+xml; charset=utf-8')
      ->header('Last-Modified', Headers::date($items[0]['date'] ?? null))
    ;
  }
}