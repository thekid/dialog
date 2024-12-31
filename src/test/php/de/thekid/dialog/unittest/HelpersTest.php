<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\Helpers;
use test\{Assert, Test, Values};

class HelpersTest {
  private $helpers= [...new Helpers()->helpers()];

  #[Test, Values([
    [['2022-11-29', '2022-12-01', 'format' => 'd.m.Y'], '29.11.2022 — 01.12.2022'],
    [['2022-11-29', '2022-11-29', 'format' => 'd.m.Y'], '29.11.2022'],
    [['2022-11-29', '2022-12-01', 'format' => 'M Y'], 'Nov 2022 — Dec 2022'],
    [['2022-11-29', '2022-11-30', 'format' => 'M Y'], 'Nov 2022'],
  ])]
  public function range($options, $expected) {
    Assert::equals($expected, $this->helpers['range'](null, null, $options));
  }

  #[Test, Values([
    [['2022-12-20', '2023-01-13', '2022-12-14'], 'future'],
    [['2022-12-20', '2023-01-13', '2022-12-20'], 'current'],
    [['2022-12-20', '2023-01-13', '2022-12-24'], 'current'],
    [['2022-12-20', '2023-01-13', '2023-01-13'], 'current'],
    [['2022-12-20', '2023-01-13', '2023-02-10'], 'passed'],
  ])]
  public function range_rel($options, $expected) {
    Assert::equals($expected, $this->helpers['range-rel'](null, null, $options));
  }

  #[Test, Values([
    [[25.0, 25.0], '25.0'],
    [[25.0, 25.4], '25.2'],
    [[25.2, 25.4, 'tolerance' => 0], '25.2 — 25.4'],
    [[25.4, 27.2], '25.4 — 27.2'],
    [[-1.5, 1.5], '-1.5 — 1.5'],
  ])]
  public function temperature($options, $expected) {
    Assert::equals($expected, $this->helpers['temperature'](null, null, $options));
  }

  #[Test, Values([
    [['slug' => 'test', 'is' => ['journey' => true]], 'journey/test'],
    [['slug' => 'test', 'is' => ['content' => true]], 'content/test'],
    [['slug' => 'test/child', 'parent' => 'test', 'is' => ['content' => true]], 'journey/test#child'],
  ])]
  public function route($entry, $expected) {
    Assert::equals($expected, $this->helpers['route'](null, null, [$entry]));
  }

  #[Test]
  public function dataset() {
    Assert::equals(
      ' data-string="value" data-yes="1" data-no="" data-number="2022"',
      $this->helpers['dataset'](null, null, [[
        'string' => 'value',
        'yes'    => true,
        'no'     => false,
        'number' => 2022,
        'array'  => ['ignored'],
        'map'    => ['also' => 'ignored'],
        'nulls'  => null,
      ]])
    );
  }

  #[Test, Values([[0, 'empty'], [1, 'single'], [2, 'even'], [3, 'odd']])]
  public function size_class($size, $expected) {
    Assert::equals($expected, $this->helpers['size-class'](null, null, [$size]));
  }
}