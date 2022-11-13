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
    if ('(' === ($q[0] ?? null)) {
      $hsl= sscanf($q, '(%d,%d,%d)');
      $range= [
        'h' => [max(0, $hsl[0] - 5), min(360, $hsl[0] + 5)],
        's' => [max(0, $hsl[1] - 20), min(100, $hsl[1] + 20)],
        'l' => [max(0, $hsl[2] - 20), min(100, $hsl[2] + 20)],
      ];

      $cursor= $this->repository->database->collection('entries')->aggregate([
        ['$match' => [
          'images.meta.palette.h' => ['$gte' => $range['h'][0], '$lte' => $range['h'][1]],
          'images.meta.palette.s' => ['$gte' => $range['s'][0], '$lte' => $range['s'][1]],
          'images.meta.palette.l' => ['$gte' => $range['l'][0], '$lte' => $range['l'][1]],
        ]]
      ]);

      $images= fn() => {
        foreach ($cursor as $result) {
          foreach ($result['images'] as $image) {
            $color= $image['meta']['palette'][0];
            if (
              ($color['h'] >= $range['h'][0] && $color['h'] <= $range['h'][1]) &&
              ($color['s'] >= $range['s'][0] && $color['s'] <= $range['s'][1]) &&
              ($color['l'] >= $range['l'][0] && $color['l'] <= $range['l'][1])
            ) {
              yield $image + ['in' => ['slug' => $result['slug']], 'matching' => $color];
            }
          }
        }
      };

      // Search all of these
      $colors= [];
      $colors[]= ['h' => $range['h'][0], 's' => $range['s'][0], 'l' => $range['l'][0]];
      $colors[]= ['h' => $range['h'][0], 's' => $range['s'][0], 'l' => $hsl[2]];
      $colors[]= ['h' => $range['h'][0], 's' => $hsl[1], 'l' => $hsl[2]];
      $colors[]= ['h' => $hsl[0], 's' => $hsl[1], 'l' => $hsl[2]];
      $colors[]= ['h' => $range['h'][1], 's' => $hsl[1], 'l' => $hsl[2]];
      $colors[]= ['h' => $range['h'][1], 's' => $range['s'][1], 'l' => $hsl[2]];
      $colors[]= ['h' => $range['h'][1], 's' => $range['s'][1], 'l' => $range['l'][1]];
      return View::named('colors')->with(['results' => ['images' => [...$images()]], 'colors' => $colors]);
    }

    $this->timer->start();
    $result= $this->repository->search(trim($q), $this->pagination, $page);
    $this->timer->stop();

    return View::named('search')->with([
      'meta'    => $result->meta,
      'results' => $result->documents,
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