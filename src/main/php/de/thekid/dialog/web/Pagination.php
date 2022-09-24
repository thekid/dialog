<?php namespace de\thekid\dialog\web;

/** @test de.thekid.dialog.unittest.PaginationTest */
class Pagination {

  public function __construct(private int $limit) { }

  /** Returns the MongoDB pipeline stages `$skip` and `$limit` */
  public function pipeline(int $page): array {
    $page < 1 && $page= 1;
    return [
      ['$skip'  => ($page - 1) * $this->limit],
      ['$limit' => $this->limit + 1],
    ];
  }

  /** Returns the paginated elements as well as the links for previous and next */
  public function paginate(int $page, iterable $elements): array<string, mixed> {
    $page < 1 && $page= 1;
    $r= [...$elements];
    if (sizeof($r) > $this->limit) {
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