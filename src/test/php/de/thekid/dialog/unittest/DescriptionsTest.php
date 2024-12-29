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
      $this->parse("---\ndate: 2024-12-29 13:19:00 {$tz}\n---\nContent")->meta
    );
  }
}