<?php namespace de\thekid\dialog\import;

use de\thekid\dialog\processing\Files;
use util\Date;

/** Imports contents */ 
class Content extends Source {

  public function entryFrom(Description $description): array<string, mixed> {
    $date= $description->meta['date'];
    return [
      'slug'      => $this->name(),
      'parent'    => $this->parent(),
      'date'      => $description->meta['date'],
      'title'     => $description->meta['title'],
      'keywords'  => $description->meta['keywords'] ?? [],
      'content'   => $description->content,
      'locations' => [...$description->locations(($date instanceof Date ? $date : new Date($date))->getTimeZone())],
      'is'        => ['content' => true],
    ];
  }

  public function contentsIn(Files $files): iterable {
    yield from $this->mediaIn($files);
  }
}