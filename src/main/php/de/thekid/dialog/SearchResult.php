<?php namespace de\thekid\dialog;

use com\mongodb\Document;

/** @see de.thekid.dialog.Repository::search */
class SearchResult {
  public static $EMPTY= new self(new Document(), []);

  public function __construct(
    public readonly Document $meta,
    public readonly iterable $documents,
  ) { }
}