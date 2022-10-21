<?php namespace de\thekid\dialog\web;

use de\thekid\dialog\{Repository, Pagination};
use util\profiling\Timer;
use web\frontend\{Handler, Get, Param, View};

#[Handler('/search')]
class Search {
  private $timer= new Timer();
  private $pagination= new Pagination(10);

  public function __construct(private Repository $repository) { }

  #[Get]
  public function search(#[Param] $q, #[Param] $page= 1) {
    $this->timer->start();
    [$meta, $results]= $this->repository->search(trim($q), $this->pagination, $page);
    $this->timer->stop();

    return View::named('search')->with([
      'meta'    => $meta,
      'results' => $results,
      'time'    => sprintf('%.3f', $this->timer->elapsedTime()),
      'excerpt' => function($node, $context, $options) {
        foreach ($options[0]['meta']['highlights'] as $highlight) {
          if ($options[1] !== $highlight['path']) continue;

          $r= '';
          foreach ($highlight['texts'] as $text) {
            if ('hit' === $text['type']) {
              $r.= '<em>'.$text['value'].'</em>';
            } else {
              $r.= $text['value'];
            }
          }
          return strip_tags($r, ['em']);
        }

        // Fall back to field content
        return strip_tags($options[0][$options[2] ?? $options[1]]);
      }
    ]);
  }
}