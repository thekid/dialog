<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\Pagination;
use lang\IllegalArgumentException;
use unittest\{Assert, Expect, Test, Values};

class PaginationTest {
  private const PAGED = 5;

  #[Test]
  public function can_create() {
    new Pagination(self::PAGED);
  }

  #[Test, Expect(IllegalArgumentException::class), Values([-1, 0])]
  public function paged_must_be_higher_than_zero($paged) {
    new Pagination($paged);
  }

  #[Test]
  public function limit() {
    Assert::equals(self::PAGED + 1, new Pagination(self::PAGED)->limit());
  }

  #[Test, Values([[1, 0], [2, self::PAGED], [3, self::PAGED * 2]])]
  public function skip($page, $expected) {
    Assert::equals($expected, new Pagination(self::PAGED)->skip($page));
  }

  #[Test]
  public function paginate_empty() {
    Assert::equals(
      ['elements' => [], 'page' => 1, 'previous' => null, 'next' => null],
      new Pagination(self::PAGED)->paginate(1, []),
    );
  }

  #[Test]
  public function paginate_less_than_paged() {
    Assert::equals(
      ['elements' => [1, 2, 3], 'page' => 1, 'previous' => null, 'next' => null],
      new Pagination(self::PAGED)->paginate(1, [1, 2, 3]),
    );
  }

  #[Test]
  public function paginate_less_than_paged_on_page_2() {
    Assert::equals(
      ['elements' => [1, 2, 3], 'page' => 2, 'previous' => 1, 'next' => null],
      new Pagination(self::PAGED)->paginate(2, [1, 2, 3]),
    );
  }

  #[Test]
  public function paginate() {
    Assert::equals(
      ['elements' => [1, 2, 3, 4, 5], 'page' => 1, 'previous' => null, 'next' => 2],
      new Pagination(self::PAGED)->paginate(1, [1, 2, 3, 4, 5, 6]),
    );
  }

  #[Test, Values([-1, 0])]
  public function skip_handles_illegal_pages_gracefully($page) {
    Assert::equals(0, new Pagination(self::PAGED)->skip($page));
  }

  #[Test, Values([-1, 0])]
  public function paginate_handles_illegal_pages_gracefully($page) {
    Assert::equals(
      ['elements' => [], 'page' => 1, 'previous' => null, 'next' => null],
      new Pagination(self::PAGED)->paginate($page, []),
    );
  }
}