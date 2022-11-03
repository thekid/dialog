<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\Helpers;
use unittest\{Assert, Test, Values};

class HelpersTest {
  private $helpers= [...new Helpers()->helpers()];

  #[Test, Values([
    [['2022-11-29', '2022-12-01', 'format' => 'd.m.Y'], '29.11.2022 - 01.12.2022'],
    [['2022-11-29', '2022-11-29', 'format' => 'd.m.Y'], '29.11.2022'],
    [['2022-11-29', '2022-12-01', 'format' => 'M Y'], 'Nov 2022 - Dec 2022'],
    [['2022-11-29', '2022-11-30', 'format' => 'M Y'], 'Nov 2022'],
  ])]
  public function range($options, $expected) {
    Assert::equals($expected, $this->helpers['range'](null, null, $options));
  }

  #[Test, Values([
    [['slug' => 'test', 'is' => ['journey' => true]], 'journey/test'],
    [['slug' => 'test', 'is' => ['content' => true]], 'content/test'],
    [['slug' => 'test/child', 'parent' => 'test', 'is' => ['content' => true]], 'journey/test#child'],
  ])]
  public function route($entry, $expected) {
    Assert::equals($expected, $this->helpers['route'](null, null, [$entry]));
  }
}