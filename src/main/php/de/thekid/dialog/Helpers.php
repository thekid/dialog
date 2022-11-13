<?php namespace de\thekid\dialog;

use web\frontend\helpers\Extension;

/**
 * Dialog handlebars helpers
 *
 * - range <from> <until>
 * - route <entry>
 *
 * @test  de.thekid.dialog.unittest.HelpersTest
 */ 
class Helpers extends Extension {

  /** @return iterable */
  public function helpers() {
    yield 'range' => function($node, $context, $options) {
      $from= date($options['format'], strtotime($options[0]));
      $until= date($options['format'], strtotime($options[1]));
      return $from === $until ? $from : $from.' - '.$until;
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
    yield 'color' => function($node, $context, $options) {
      $input= $options[0];
      return sprintf(
        '#%02x%02x%02x', 
        ($input >> 16) & 0xff,
        ($input >> 8) & 0xff,
        $input & 0xff,
      );
    };
  }
}