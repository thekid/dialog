<?php namespace de\thekid\dialog\unittest;

use com\mongodb\Document;
use de\thekid\dialog\Optional;
use lang\IllegalStateException;
use unittest\{Assert, Expect, Test};

class OptionalTest {
  private $document= new Document(['_id' => 1234]);

  #[Test]
  public function can_create_with_null() {
    new Optional(null);
  }

  #[Test]
  public function can_create_with_document() {
    new Optional($this->document);
  }

  #[Test]
  public function present() {
    Assert::false(new Optional(null)->present());
    Assert::true(new Optional($this->document)->present());
  }

  #[Test]
  public function get_when_present() {
    Assert::equals($this->document, new Optional($this->document)->get());
  }

  #[Test]
  public function get_when_null() {
    Assert::null(new Optional(null)->get());
  }

  #[Test]
  public function or_when_present() {
    Assert::equals(
      $this->document,
      new Optional($this->document)->or(fn() => throw new IllegalStateException('Test'))
    );
  }

  #[Test]
  public function or_executes_callable_when_null() {
    Assert::equals(
      $this->document,
      new Optional($this->document)->or(fn() => $this->document)
    );
  }

  #[Test, Expect(IllegalStateException::class)]
  public function or_raises_exceptions_from_callable() {
    new Optional(null)->or(fn() => throw new IllegalStateException('Test'));
  }
}