<?php namespace de\thekid\dialog\web;

use de\thekid\dialog\{Repository, Pagination};
use web\frontend\{Handler, Get, Param};

#[Handler('/')]
class Feed {
  private $pagination= new Pagination(5);

  public function __construct(private Repository $repository) { }

  #[Get]
  public function listing(#[Param] $page= 1) {
    return $this->repository->entries($this->pagination, (int)$page);
  }
}