<?php namespace de\thekid\dialog;

use web\frontend\helpers\Extension;

/**
 * Dialog handlebars helpers
 *
 * - range <from> <until>
 * - range-rel <from> <until>
 * - size-class <size>
 * - route <entry>
 * - dataset <meta-inf>
 * - sign <link>
 *
 * @test  de.thekid.dialog.unittest.HelpersTest
 */ 
class Helpers extends Extension {

  public function __construct(private ?Signing $signing= null) { }

  /** @return iterable */
  public function helpers() {
    yield 'range' => function($node, $context, $options) {
      $from= date($options['format'], strtotime($options[0]));
      $until= date($options['format'], strtotime($options[1]));
      return $from === $until ? $from : $from.' - '.$until;
    };
    yield 'range-rel' => function($node, $context, $options) {
      $from= strtotime($options[0]);
      $until= strtotime($options[1]);
      $time= isset($options[2]) ? strtotime($options[2]) : time();
      if ($time < $from) return 'future';
      if ($time > $until) return 'passed';
      return 'current';
    };
    yield 'size-class' => function($node, $context, $options) {
      $s= (int)$options[0];
      return match {
        0 === $s => 'empty',
        1 === $s => 'single',
        1 === $s % 2 => 'odd',
        default => 'even',
      };
    };
    yield 'route' => function($node, $context, $options) {
      $entry= $options[0];
      if (isset($entry['is']['journey'])) {
        return 'journey/'.$entry['slug'];
      } else if (isset($entry['parent'])) {
        return 'journey/'.strtr($entry['slug'], ['/' => '#']);
      } else {
        return 'content/'.$entry['slug'];
      }
    };
    yield 'dataset' => function($node, $context, $options) {
      $r= '';
      foreach ($options[0] as $key => $value) {
        is_scalar($value) && $r.= ' data-'.htmlspecialchars($key).'="'.htmlspecialchars($value).'"';
      }
      return $r;
    };
    yield 'wrap' => function($node, $context, $options) {
      return $options[0][$options[1] % sizeof($options[0])];
    };
    yield 'sign' => function($node, $context, $options) {
      return $this->signing?->sign($options[0]);
    };
  }
}