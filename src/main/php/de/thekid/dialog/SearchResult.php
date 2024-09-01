<?php namespace de\thekid\dialog;

use com\mongodb\Document;

/** @see de.thekid.dialog.Repository::search */
readonly class SearchResult {
  public static $EMPTY= new self(new Document(), []);

  public function __construct(
    public Document $meta,
    public iterable $documents,
  ) { }
}