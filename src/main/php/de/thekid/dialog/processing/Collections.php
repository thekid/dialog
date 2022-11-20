<?php namespace de\thekid\dialog\processing;

use com\mongodb\{Collection, Document};
use lang\Throwable;
use util\Date;

class Collections {

  private function __construct(private Collection $collection, private iterable $items) { }

  /**
   * Returns all documents in the given collection. Includes currently
   * existing documents as well as anything inserted or updated with
   * its `state` field set to "new".
   */
  public static function watching(Collection $collection): self {
    $generator= fn() => {
      static $options= ['fullDocument' => 'updateLookup'];

      // Process all currently existing items
      yield from $collection->find(['state' => 'new']);

      // Watch the collection for changes
      foreach ($collection->watch([['$match' => ['fullDocument.state' => 'new']]], $options) as $change) {
        yield new Document($change['fullDocument']);
      }
    };
    return new self($collection, $generator());
  }

  public function each(function(Document): iterable $apply): int {
    $i= 0;
    foreach ($this->items as $item) {
      try {
        foreach ($apply($item) as $state => $value) {
          $this->collection->update($item->id(), ['$set' => [
            'state' => $state,
            'value' => $value,
            'at'    => Date::now(),
          ]]);
        }
      } catch ($e) {
        Throwable::wrap($e)->printStackTrace();
        // TODO: Error handling
      }
      $i++;
    }
    return $i;
  } 
}