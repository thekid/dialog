<?php namespace de\thekid\dialog\import;

use io\{Folder, File};
use lang\{Enum, IllegalArgumentException};

abstract class Sources extends Enum {
  protected static $descriptions= new Descriptions();
  public static $CONTENT, $JOURNEY, $COVER;

  static function __static() {
    self::$CONTENT= new class(0, 'content') extends self {
      public function file(Folder $origin): File { return new File($origin, 'content.md'); }
      public function items(Folder $origin): iterable {
        $d= self::$descriptions->parse($this->file($origin));
        yield $origin => [
          'slug'      => $origin->dirname,
          'date'      => $d->meta['date'],
          'title'     => $d->meta['title'],
          'locations' => isset($d->meta['location']) ? [$d->meta['location']] : $d->meta['locations'],
          'content'   => $d->content,
          'is'        => ['content' => true],
        ];
      }
    };
    self::$JOURNEY= new class(1, 'journey') extends self {
      public function file(Folder $origin): File { return new File($origin, 'journey.md'); }

      public function items(Folder $origin): iterable {
        $d= self::$descriptions->parse($this->file($origin));
        yield $origin => [
          'slug'      => $origin->dirname,
          'date'      => $d->meta['from'],
          'title'     => $d->meta['title'],
          'locations' => isset($d->meta['location']) ? [$d->meta['location']] : $d->meta['locations'],
          'content'   => $d->content,
          'is'        => [
            'journey' => true,
            'from'    => $d->meta['from'],
            'until'   => $d->meta['until'],
          ],
        ];

        // Subfolders contain contents
        foreach ($origin->entries() as $entry) {
          if (!$entry->isFolder()) continue;
          
          foreach (self::$CONTENT->items($entry->asFolder()) as $f => $entry) {
            yield $f => ['parent' => $origin->dirname, 'slug' => $origin->dirname.'/'.$entry['slug']] + $entry;
          }
        }
      }
    };
    self::$COVER= new class(2, 'cover') extends self {
      public function file(Folder $origin): File { return new File($origin, 'cover.md'); }
      public function items(Folder $origin): iterable {
        $d= self::$descriptions->parse($this->file($origin));
        yield $origin => [
          'slug'      => $origin->dirname,
          'parent'    => '~',
          'date'      => $d->meta['date'],
          'title'     => $d->meta['title'],
          'locations' => isset($d->meta['location']) ? [$d->meta['location']] : $d->meta['locations'],
          'content'   => $d->content,
          'is'        => ['content' => true],
        ];
      }
    };
  }

  public abstract function file(Folder $origin): File;

  public abstract function items(Folder $origin): iterable;

  public static function in(Folder $origin): iterable {
    foreach ([self::$CONTENT, self::$JOURNEY, self::$COVER] as $source) {
      if ($source->file($origin)->exists()) return $source->items($origin);
    }
    throw new IllegalArgumentException('Cannot import '.$origin->getURI());
  }
}
