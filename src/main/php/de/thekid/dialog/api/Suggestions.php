<?php namespace de\thekid\dialog\api;

use de\thekid\dialog\Repository;
use web\rest\{Resource, Get, Param};

#[Resource('/api/suggestions')]
class Suggestions {

  public function __construct(private Repository $repository) { }

  #[Get]
  public function suggest(#[Param] $q) {
    foreach ($this->repository->suggest(trim($q)) as $suggestion) {
      $kind= key($suggestion['is']);
      yield [
        'kind'  => $kind,
        'title' => $suggestion['title'],
        'date'  => $suggestion['date']->toString('d.m.Y'),
        'at'    => $suggestion['at'],
        'link'  => ('journey' === $kind
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