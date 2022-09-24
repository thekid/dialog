<?php namespace de\thekid\dialog;

use lang\IllegalArgumentException;

/** @test de.thekid.dialog.unittest.PaginationTest */
class Pagination {

  /** @throws lang.IllegalArgumentException */
  public function __construct(private int $paged) {
    if ($paged < 1) {
      throw new IllegalArgumentException('Paged must be greater than 0');
    }
  }

  /** Returns how many items to skip */
  public function skip(int $page): int { return $page < 1 ? 0 : ($page - 1) * $this->paged; }

  /** Returns limit */
  public function limit(): int { return $this->paged + 1; }

  /** Returns the paginated elements as well as the links for previous and next */
  public function paginate(int $page, iterable $elements): array<string, mixed> {
    $page < 1 && $page= 1;
    $r= [...$elements];
    if (sizeof($r) > $this->paged) {
      array_pop($r);
      $next= $page + 1;
    } else {
      $next= null;
    }

    return [
      'elements' => $r,
      'page'     => $page,
      'previous' => $page > 1 ? $page - 1 : null,
      'next'     => $next
    ];
  }
}