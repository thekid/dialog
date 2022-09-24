<?php namespace de\thekid\dialog;

use com\mongodb\result\Update;
use com\mongodb\{Database, Document};
use text\hash\Hashing;
use util\{Date, Secret};

class Repository {

  public function __construct(private Database $database) { }

  /** Authenticates a given user, returning NULL on failure */
  public function authenticate(string $user, Secret $secret): ?Document {
    $cursor= $this->database->collection('users')->find([
      'handle' => $user,
      'hash'   => Hashing::sha256()->digest($secret->reveal())->hex()
    ]);
    return $cursor->first();
  }

  /** Returns paginated (top-level) entries */
  public function entries(Pagination $pagination, int $page): iterable {
    return $pagination->paginate($page, $this->database->collection('entries')->aggregate([
      ['$match' => ['parent' => ['$eq' => null], 'published' => ['$lt' => Date::now()]]],
      ['$sort'  => ['date' => -1]],
      ['$skip'  => $pagination->skip($page)],
      ['$limit' => $pagination->limit()],
    ]));
  }

  /** Returns a single entry */
  public function entry(string $slug): ?Document {
    $cursor= $this->database->collection('entries')->find([
      'slug'      => ['$eq' => $slug],
      'published' => ['$lt' => Date::now()],
    ]);
    return $cursor->first();
  }

  /** Returns an entry's children */
  public function children(string $slug): iterable {
    $cursor= $this->database->collection('entries')->aggregate([
      ['$match' => ['parent' => ['$eq' => $slug], 'published' => ['$lt' => Date::now()]]],
      ['$sort'  => ['date' => -1]],
    ]);
    return $cursor->all();
  }

  /** Replace an entry identified by a given slug with a given entity */
  public function replace(string $slug, array<string, mixed> $entity): Update {
    return $this->database->collection('entries')->upsert(
      ['slug' => $slug],
      new Document(['slug' => $slug] + $entity),
    );
  }

  /** Modify an entry identified by a given slug with MongoDB statements */
  public function modify(string $slug, array<string, mixed> $statements): Update {
    return $this->database->collection('entries')->update(
      ['slug' => $slug],
      $statements,
    );
  }
}