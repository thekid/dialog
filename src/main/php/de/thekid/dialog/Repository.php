<?php namespace de\thekid\dialog;

use com\mongodb\result\{Cursor, Update, Modification, Delete};
use com\mongodb\{Collection, Document, MongoConnection};
use text\hash\Hashing;
use util\{Date, Secret};

class Repository {
  private const WITH_PARENT= [
    ['$lookup' => [
      'from'         => 'entries',
      'localField'   => 'parent',
      'foreignField' => 'slug',
      'as'           => 'parent',
    ]],
    ['$addFields' => ['parent' => ['$first' => '$parent']]],
  ];
  private $passwords= Hashing::sha256();
  private $collections= [];

  public function __construct(private MongoConnection $conn, private string $database) { }

  /** Memoizes and returns a named collection */
  private function collection(string $name): Collection {
    return $this->collections[$name]??= $this->conn->collection($this->database, $name);
  }

  /** Authenticates a given user, returning NULL on failure */
  public function authenticate(string $user, Secret $secret): ?Document {
    $cursor= $this->collection('users')->find([
      'handle' => $user,
      'hash'   => $this->passwords->digest($secret->reveal())->hex()
    ]);
    return $cursor->first();
  }

  /** Returns newest entries */
  public function newest(int $limit): array<Document> {
    $cursor= $this->collection('entries')->aggregate([
      ['$match' => ['is.content' => ['$eq' => true], 'published' => ['$lt' => Date::now()]]],
      ['$unset' => '_searchable'],
      ['$sort'  => ['date' => -1]],
      ['$limit' => $limit],
      ...self::WITH_PARENT,
    ]);
    return $cursor->all();
  }

  /** Returns all journeys */
  public function journeys(): array<Document> {
    $cursor= $this->collection('entries')->aggregate([
      ['$match' => ['is.journey' => ['$eq' => true], 'published' => ['$lt' => Date::now()]]],
      ['$unset' => '_searchable'],
      ['$sort'  => ['date' => -1]],
    ]);
    return $cursor->all();
  }

  /** Returns paginated entries */
  public function entries(Pagination $pagination, int $page): array<Document> {
    $cursor= $this->collection('entries')->aggregate([
      ['$match'  => ['is.content' => ['$eq' => true], 'published' => ['$lt' => Date::now()]]],
      ['$unset'  => '_searchable'],
      ['$sort'   => ['date' => -1]],
      ['$skip'   => $pagination->skip($page)],
      ['$limit'  => $pagination->limit()],
      ...self::WITH_PARENT,
    ]);
    return $pagination->paginate($page, $cursor);
  }

  /** Returns search suggestions */
  public function suggest(string $query, int $limit= 10): iterable {
    $autocomplete= [
      'should'  => [
        ['autocomplete' => ['query' => $query, 'path' => 'title', 'score' => ['boost' => ['value' => 5.0]]]],
        ['autocomplete' => ['query' => $query, 'path' => '_searchable.suggest', 'score' => ['boost' => ['path' => '_searchable.boost']]]],
      ],
      'mustNot' => ['text' => ['path' => 'slug', 'query' => '@cover']],
    ];
    return '' === $query ? [] : $this->collection('entries')->aggregate([
      ['$search'    => ['index' => $this->database, 'compound' => $autocomplete]],
      ['$addFields' => ['at' => '$locations.name']],
      ['$unset'     => '_searchable'],
      ['$limit'     => $limit],
    ]);
  }

  /** Performs search */
  public function search(string $query, Pagination $pagination, int $page): SearchResult {
    static $fields= ['title', 'keywords', '_searchable.content'];
    static $fuzzy= ['fuzzy' => ['maxEdits' => 1]];

    // Handle egde case
    if ('' === $query) return SearchResult::$EMPTY;

    // Rank as follows:
    // - A direct hit on a location name
    // - A direct hit in the title
    // - Phrase contained in the content
    // - Fuzzy matching on title and content
    $search= [
      'should' => [
        ['text'   => ['query' => $query, 'path' => 'locations.name', 'score' => ['boost' => ['value' => 5.0]]]],
        ['phrase' => ['query' => $query, 'path' => 'title', 'score' => ['boost' => ['value' => 2.0]]]],
        ['phrase' => ['query' => $query, 'path' => $fields, 'score' => ['boost' => ['path' => '_searchable.boost']]]],
        ['text'   => $fuzzy + ['query' => $query, 'path' => $fields, 'score' => ['boost' => ['value' => 0.2]]]],
      ],
      'mustNot' => [
        ['text' => ['path' => 'slug', 'query' => '@cover']
      ]],
    ];
    $meta= $this->collection('entries')->aggregate([
      ['$searchMeta' => [
        'index'    => $this->database,
        'count'    => ['type' => 'lowerBound'],
        'compound' => $search,
      ]]
    ]);
    $cursor= $this->collection('entries')->aggregate([
      ['$search'    => [
        'index'     => $this->database,
        'compound'  => $search,
        'highlight' => ['path' => '_searchable.content', 'maxNumPassages' => 3]
      ]],
      ['$unset'     => '_searchable'],
      ['$addFields' => ['meta' => ['highlights' => ['$meta' => 'searchHighlights']]]],
      ['$skip'      => $pagination->skip($page)],
      ['$limit'     => $pagination->limit()],
    ]);
    return new SearchResult($meta->first(), $pagination->paginate($page, $cursor));
  }

  /** Returns a single entry */
  public function entry(string $slug, bool $published= true): ?Document {
    $cursor= $this->collection('entries')->aggregate([
      ['$match' => ['slug' => $slug] + ($published ? ['published' => ['$lt' => Date::now()]] : [])],
      ['$unset' => '_searchable'],
    ]);
    return $cursor->first();
  }

  /** Returns an entry's children, latest first */
  public function children(string $slug, bool $published= true, array<string, mixed> $sort= ['date' => -1]): Cursor {
    return $this->collection('entries')->aggregate([
      ['$match' => ['parent' => $slug] + ($published ? ['published' => ['$lt' => Date::now()]] : [])],
      ['$unset' => '_searchable'],
      ['$sort'  => $sort],
    ]);
  }

  /** Replace an entry identified by a given slug with a given entity */
  public function replace(string $slug, array<string, mixed> $entity): Modification {
    return $this->collection('entries')->modify(
      ['slug' => $slug],
      ['$set' => ['slug' => $slug, ...$entity]],
      upsert: true,
    );
  }

  /** Modify an entry identified by a given slug with MongoDB statements */
  public function modify(string $slug, array<string, mixed> $statements): Update {
    return $this->collection('entries')->update(
      ['slug' => $slug],
      $statements,
    );
  }

  /** Delete an entry identified by a given slug */
  public function delete(string $slug): Delete {
    return $this->collection('entries')->delete(['slug' => $slug]);
  }
}