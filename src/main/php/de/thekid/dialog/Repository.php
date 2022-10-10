<?php namespace de\thekid\dialog;

use com\mongodb\result\Update;
use com\mongodb\{Database, Document};
use text\hash\Hashing;
use util\{Date, Secret};

class Repository {
  private $passwords= Hashing::sha256();

  public function __construct(private Database $database) { }

  /** Authenticates a given user, returning NULL on failure */
  public function authenticate(string $user, Secret $secret): ?Document {
    $cursor= $this->database->collection('users')->find([
      'handle' => $user,
      'hash'   => $this->passwords->digest($secret->reveal())->hex()
    ]);
    return $cursor->first();
  }

  /** Returns newest (top-level) entries */
  public function newest(int $limit): array<Document> {
    $cursor= $this->database->collection('entries')->aggregate([
      ['$match' => ['parent' => ['$eq' => null], 'published' => ['$lt' => Date::now()]]],
      ['$sort'  => ['date' => -1]],
      ['$limit' => $limit],
    ]);
    return $cursor->all();
  }

  /** Returns all journeys */
  public function journeys(): array<Document> {
    $cursor= $this->database->collection('entries')->aggregate([
      ['$match' => ['is.journey' => ['$eq' => true], 'published' => ['$lt' => Date::now()]]],
      ['$sort'  => ['date' => -1]],
    ]);
    return $cursor->all();
  }

  /** Returns paginated (top-level) entries */
  public function entries(Pagination $pagination, int $page): array<Document> {
    return $pagination->paginate($page, $this->database->collection('entries')->aggregate([
      ['$match' => ['parent' => ['$eq' => null], 'published' => ['$lt' => Date::now()]]],
      ['$sort'  => ['date' => -1]],
      ['$skip'  => $pagination->skip($page)],
      ['$limit' => $pagination->limit()],
    ]));
  }

  /** Returns a single entry */
  public function entry(string $slug, bool $published= true): ?Document {
    return $this->database->collection('entries')
      ->find(['slug' => $slug] + ($published ? ['published' => ['$lt' => Date::now()]] : []))
      ->first()
    ;
  }

  /** Returns an entry's children */
  public function children(string $slug): array<Document> {
    $cursor= $this->database->collection('entries')->aggregate([
      ['$match' => ['parent' => ['$eq' => $slug], 'published' => ['$lt' => Date::now()]]],
      ['$sort'  => ['date' => -1]],
    ]);
    return $cursor->all();
  }

  /** Replace an entry identified by a given slug with a given entity */
  public function replace(string $slug, array<string, mixed> $entity): Modification {
    $arguments= [
      'query'  => ['slug' => $slug],
      'update' => ['$set' => ['slug' => $slug, ...$entity]],
      'new'    => true,  // Return modified document
      'upsert' => true,
    ];
    return new Modification($this->database->collection('entries')->run('findAndModify', $arguments)->value());
  }

  /** Modify an entry identified by a given slug with MongoDB statements */
  public function modify(string $slug, array<string, mixed> $statements): Update {
    return $this->database->collection('entries')->update(
      ['slug' => $slug],
      $statements,
    );
  }
}