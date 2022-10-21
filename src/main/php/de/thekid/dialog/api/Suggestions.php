<?php namespace de\thekid\dialog\api;

use de\thekid\dialog\Repository;
use web\rest\{Resource, Get, Param};

#[Resource('/api/suggestions')]
class Suggestions {

  public function __construct(private Repository $repository) { }

  #[Get]
  public function suggest(#[Param] $q) {
    foreach ($this->repository->suggest(trim($q)) as $suggestion) {
      yield [
        'title' => $suggestion['title'],
        'date'  => $suggestion['date']->toString('d.m.Y'),
        'link'  => (isset($suggestion['is']['journey'])
          ? '/journey/'.$suggestion['slug']
          : (isset($suggestion['parent']) 
            ? '/journey/'.strtr($suggestion['slug'], ['/' => '#'])
            : '/content/'.$suggestion['slug']
          )
        )
      ];
    }
  }
}