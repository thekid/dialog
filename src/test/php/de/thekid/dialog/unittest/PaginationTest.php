<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\web\Pagination;
use unittest\{Assert, Test, Values};

class PaginationTest {
  private const LIMIT = 5;

  #[Test]
  public function can_create() {
    new Pagination(self::LIMIT);
  }

  #[Test, Values([
    [1, [['$skip' => 0], ['$limit' => 6]]],
    [2, [['$skip' => 5], ['$limit' => 6]]],
  ])]
  public function pipeline($page, $expected) {
    Assert::equals($expected, new Pagination(self::LIMIT)->pipeline($page));
  }

  #[Test]
  public function paginate_empty() {
    Assert::equals(
      ['elements' => [], 'page' => 1, 'previous' => null, 'next' => null],
      new Pagination(self::LIMIT)->paginate(1, []),
    );
  }

  #[Test]
  public function paginate_less_than_limit() {
    Assert::equals(
      ['elements' => [1, 2, 3], 'page' => 1, 'previous' => null, 'next' => null],
      new Pagination(self::LIMIT)->paginate(1, [1, 2, 3]),
    );
  }

  #[Test]
  public function paginate_less_than_limit_on_page_2() {
    Assert::equals(
      ['elements' => [1, 2, 3], 'page' => 2, 'previous' => 1, 'next' => null],
      new Pagination(self::LIMIT)->paginate(2, [1, 2, 3]),
    );
  }

  #[Test]
  public function paginate() {
    Assert::equals(
      ['elements' => [1, 2, 3, 4, 5], 'page' => 1, 'previous' => null, 'next' => 2],
      new Pagination(self::LIMIT)->paginate(1, [1, 2, 3, 4, 5, 6]),
    );
  }

  #[Test, Values([-1, 0])]
  public function pipeline_handles_illegal_pages_gracefully($page) {
    Assert::equals([['$skip' => 0], ['$limit' => 6]], new Pagination(self::LIMIT)->pipeline($page));
  }

  #[Test, Values([-1, 0])]
  public function paginate_handles_illegal_pages_gracefully($page) {
    Assert::equals(
      ['elements' => [], 'page' => 1, 'previous' => null, 'next' => null],
      new Pagination(self::LIMIT)->paginate($page, []),
    );
  }
}