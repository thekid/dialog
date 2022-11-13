<?php namespace de\thekid\dialog\api;

use de\thekid\dialog\{Repository, Signing};
use io\Path;
use web\rest\{Post, Resource, Body};

#[Resource('/api/statistics')]
class Statistics {

  public function __construct(
    private Repository $repository,
    private Path $storage,
    private Signing $signing
  ) { }

  #[Post('/{id:.+(/.+)?}')]
  public function update(string $id, #[Body] $signature) {
    return $this->signing->verify($id, $signature)
      ? $this->repository->modify($id, ['$inc' => ['views' => 1]])->modified()
      : 0
    ;
  }
}