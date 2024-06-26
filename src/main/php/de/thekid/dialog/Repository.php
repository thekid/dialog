<?php namespace de\thekid\dialog;

use com\mongodb\result\{Cursor, Update, Modification};
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
      ['$unset' => '_searchable'],
      ['$sort'  => ['date' => -1]],
      ['$limit' => $limit],
    ]);
    return $cursor->all();
  }

  /** Returns all journeys */
  public function journeys(): array<Document> {
    $cursor= $this->database->collection('entries')->aggregate([
      ['$match' => ['is.journey' => ['$eq' => true], 'published' => ['$lt' => Date::now()]]],
      ['$unset' => '_searchable'],
      ['$sort'  => ['date' => -1]],
    ]);
    return $cursor->all();
  }

  /** Returns paginated (top-level) entries */
  public function entries(Pagination $pagination, int $page, int $children= 6): array<Document> {
    $entries= $this->database->collection('entries');
    $cursor= $entries->aggregate([
      ['$match'  => ['parent' => ['$eq' => null], 'published' => ['$lt' => Date::now()]]],
      ['$unset'  => '_searchable'],
      ['$sort'   => ['date' => -1]],
      ['$skip'   => $pagination->skip($page)],
      ['$limit'  => $pagination->limit()],

      // If no preview images are set, aggregate children
      ['$lookup' => [
        'from'     => 'entries',
        'let'      => ['parent' => '$slug', 'images' => ['$size' => ['$ifNull' => ['$images', []]]]],
        'pipeline' => [
          ['$match' => ['$expr' => ['$cond' => [
            ['$eq' => ['$$images', 0]],
            ['$eq' => ['$parent', '$$parent']],
            ['$eq' => ['$_id', null]],
          ]]]]
        ],
        'as'       => 'children',
      ]],
      ['$addFields' => ['children' => ['$map' => [
        'input' => ['$sortArray' => ['input'  => '$children', 'sortBy' => ['date' => -1]]],
        'as'    => 'it',
        'in'    => ['$unsetField' => ['input' => '$$it', 'field' => '_searchable']],
      ]]]],
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
    return '' === $query ? [] : $this->database->collection('entries')->aggregate([
      ['$search'    => ['index' => $this->database->name(), 'compound' => $autocomplete]],
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
    $meta= $this->database->collection('entries')->aggregate([
      ['$searchMeta' => [
        'index'    => $this->database->name(),
        'count'    => ['type' => 'lowerBound'],
        'compound' => $search,
      ]]
    ]);
    $cursor= $this->database->collection('entries')->aggregate([
      ['$search'    => [
        'index'     => $this->database->name(),
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
    $cursor= $this->database->collection('entries')->aggregate([
      ['$match' => ['slug' => $slug] + ($published ? ['published' => ['$lt' => Date::now()]] : [])],
      ['$unset' => '_searchable'],
    ]);
    return $cursor->first();
  }

  /** Returns an entry's children, latest first */
  public function children(string $slug): Cursor {
    return $this->database->collection('entries')->aggregate([
      ['$match' => ['parent' => ['$eq' => $slug], 'published' => ['$lt' => Date::now()]]],
      ['$unset' => '_searchable'],
      ['$sort'  => ['date' => -1]],
    ]);
  }

  /** Replace an entry identified by a given slug with a given entity */
  public function replace(string $slug, array<string, mixed> $entity): Modification {
    return $this->database->collection('entries')->modify(
      ['slug' => $slug],
      ['$set' => ['slug' => $slug, ...$entity]],
      upsert: true,
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