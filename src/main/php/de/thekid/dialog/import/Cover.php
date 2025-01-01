<?php namespace de\thekid\dialog\import;

use de\thekid\dialog\processing\Files;
use util\Date;

/** Imports the cover image */ 
class Cover extends Source {

  /** Returns this source's name */
  public function name(): string { return '@cover'; }

  public function entryFrom(Description $description): array<string, mixed> {
    $date= $description->meta['date'];
    return [
      'slug'      => '@cover',
      'parent'    => '~',
      'date'      => $description->meta['date'],
      'title'     => $description->meta['title'],
      'keywords'  => $description->meta['keywords'] ?? [],
      'content'   => $description->content,
      'locations' => [...$description->locations(($date instanceof Date ? $date : new Date($date))->getTimeZone())],
      'is'        => ['cover' => true],
    ];
  }

  public function contentsIn(Files $files): iterable {
    yield from $this->mediaIn($files);
  }
}