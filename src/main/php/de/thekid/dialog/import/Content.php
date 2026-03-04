<?php namespace de\thekid\dialog\import;

use de\thekid\dialog\processing\Files;
use util\Date;

/** Imports contents */ 
class Content extends Source {

  public function entryFrom(Description $description): array<string, mixed> {
    $date= $description->meta['date'] instanceof Date
      ? $description->meta['date']
      : new Date($description->meta['date'])
    ;
    return [
      'slug'      => $this->name(),
      'parent'    => $this->parent(),
      'date'      => $date,
      'timezone'  => $date->getTimeZone()->name(),
      'title'     => $description->meta['title'],
      'keywords'  => $description->meta['keywords'] ?? [],
      'content'   => $description->content,
      'locations' => [...$description->locations($date->getTimeZone())],
      'is'        => ['content' => true],
    ];
  }

  public function contentsIn(Files $files): iterable {
    return yield from $this->mediaIn($files);
  }
}