<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\import\{Description, Descriptions};
use io\streams\MemoryInputStream;
use lang\FormatException;
use unittest\{Assert, Expect, Test, Values};

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
}