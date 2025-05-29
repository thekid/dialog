<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\import\{Description, Descriptions};
use io\streams\MemoryInputStream;
use lang\FormatException;
use test\{Assert, Expect, Test, Values};
use util\{Date, TimeZone};

class DescriptionsTest {

  private function parse(string $input): Description {
    return new Descriptions()->parse(new MemoryInputStream($input));
  }

  #[Test]
  public function can_create() {
    new Descriptions();
  }

  #[Test, Expect(FormatException::class), Values(['', 'Content'])]
  public function parse_input_without_yfm($input) {
    $this->parse($input);
  }

  #[Test]
  public function parse_meta() {
    Assert::equals(
      ['title' => 'Test', 'location' => 'KA'],
      $this->parse("---\ntitle: Test\nlocation: KA\n---\nContent")->meta
    );
  }

  #[Test]
  public function parse_content() {
    Assert::equals(
      '<p>Content</p>',
      $this->parse("---\ntitle: Test\nlocation: KA\n---\nContent")->content
    );
  }

  #[Test, Values([
    ['', TimeZone::getLocal()],
    ['Europe/Berlin', TimeZone::getByName('Europe/Berlin')],
    ['Asia/Muscat', TimeZone::getByName('Asia/Muscat')],
  ])]
  public function resolve_timezone($tz, $resolved) {
    Assert::equals(
      ['date' => new Date('2024-12-29 13:19:00', $resolved)],
      $this->parse("---\ndate: 2024-12-29 13:19:00 {$tz}\n---\n")->meta
    );
  }

  #[Test]
  public function passed_timezone_used() {
    $tz= 'Europe/Berlin';
    Assert::equals(
      [['name' => 'Berlin', 'timezone' => $tz]],
      [...$this->parse("---\nlocation:\n{name: \"Berlin\"}\n---\n")->locations($tz)]
    );
  }

  #[Test]
  public function location_supplied_timezone_used() {
    $tz= 'Europe/Berlin';
    Assert::equals(
      [['name' => 'Oman', 'timezone' => 'Asia/Muscat']],
      [...$this->parse("---\nlocation:\n{name: \"Oman\", timezone: \"Asia/Muscat\"}\n---\n")->locations($tz)]
    );
  }

  #[Test, Values(['Europe/Berlin', 'Asia/Muscat', 'America/New_York'])]
  public function location_timezone($tz) {
    $locations= implode("\n", [
      '  - {name: "Here"}',
      '  - {name: "Oman", timezone: "Asia/Muscat"}',
      '  - {name: "日本", timezone: "Asia/Tokyo"}',
    ]);
    Assert::equals(
      [
        ['name' => 'Here', 'timezone' => $tz],
        ['name' => 'Oman', 'timezone' => 'Asia/Muscat'],
        ['name' => '日本', 'timezone' => 'Asia/Tokyo'],
      ],
      [...$this->parse("---\nlocations:\n{$locations}\n---\n")->locations($tz)]
    );
  }
}