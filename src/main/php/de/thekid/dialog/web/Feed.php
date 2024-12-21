<?php namespace de\thekid\dialog\web;

use de\thekid\dialog\{Repository, Pagination};
use util\Date;
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
      ->modified($items[0]['date'] ?? null)
      ->cache('public, max-age=3600')
      ->type('application/atom+xml; charset=utf-8')
    ;
  }
}