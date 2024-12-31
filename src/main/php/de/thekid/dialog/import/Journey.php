<?php namespace de\thekid\dialog\import;

use de\thekid\dialog\processing\Files;
use io\File;
use util\Date;

/** Imports journeys */ 
class Journey extends Source {

  /** Subfolders of a journey form its child contents */
  private function childrenIn(Files $files): iterable {
    $children= [];
    foreach ($this->entry['$children'] as $child) {
      $children[$child['slug']]= $child;
    }

    foreach ($this->origin->entries() as $path) {
      if ($path->isFolder()) {
        $folder= $path->asFolder();
        $slug= $this->entry['slug'].'/'.$folder->dirname;

        yield from new Content($folder, new File($folder, 'content.md'), $children[$slug] ?? null)
          ->nestedIn($this->entry['slug'])
          ->synchronize($files)
        ;
        unset($children[$slug]);
      }
    }

    foreach ($children as $rest) {
      yield new DeleteEntry($rest['slug']);
    }
  }

  public function entryFrom(Description $description): array<string, mixed> {
    $date= $description->meta['from'];
    return [
      'slug'      => $this->name(),
      'date'      => $description->meta['from'],
      'title'     => $description->meta['title'],
      'keywords'  => $description->meta['keywords'] ?? [],
      'content'   => $description->content,
      'locations' => [...$description->locations(($date instanceof Date ? $date : new Date($date))->getTimeZone())],
      'is'        => [
        'journey' => true,
        'from'    => $description->meta['from'],
        'until'   => $description->meta['until'],
      ],
    ];
  }

  public function contentsIn(Files $files): iterable {
    yield from $this->mediaIn($files);
    yield from $this->childrenIn($files);
  }
}