<?php namespace de\thekid\dialog\import;

use de\thekid\dialog\processing\Files;
use io\{File, Folder};
use lang\{IllegalArgumentException, Value};
use util\Comparison;

abstract class Source implements Value {
  use Comparison;

  private static $descriptions= new Descriptions();
  private $name;

  public function __construct(protected Folder $origin, protected File $file, protected ?array<string, mixed> $entry= null) {
    $this->name= $this->origin->dirname;
  }

  /** Returns this source's name */
  public function name(): string { return $this->name; }

  /** Returns this source's parent name, if any */
  public function parent(): ?string { return strstr($this->name(), '/', true) ?: null; }

  /** Sets a parent for this source */
  public function nestedIn(string $parent): self { $this->name= $parent.'/'.$this->name; return $this; }

  /** Yields all the media files in this source */
  protected function mediaIn(Files $files): iterable {
    static $processed= '/^(thumb|preview|full|video|screen)-/';

    $images= [];
    foreach ($this->entry['images'] ?? [] as $image) {
      $images[$image['name']]= $image;
    }

    foreach ($this->origin->entries() as $path) {
      $name= $path->name();
      if ($path->isFile() && !preg_match($processed, $name) && ($processing= $files->processing($name))) {
        $file= $path->asFile();
        $name= $file->filename;

        if (!isset($images[$name]) || $file->lastModified() > $images[$name]['modified']) {
          yield new UploadMedia($this->entry['slug'], $file, $processing);
        }        
        unset($images[$name]);
      }
    }

    foreach ($images as $rest) {
      yield new DeleteMedia($entry['slug'], $rest['name']);
    }
  }

  /** Returns an entry from the given description */
  public abstract function entryFrom(Description $description): array<string, mixed>;

  /** Yields contents of this source */
  public abstract function contentsIn(Files $files): iterable;

  /** Yields tasks to synchronize this source */
  public function synchronize(Files $files) {
    $this->entry??= yield new FetchEntry($this->name());
    if (!isset($this->entry['modified']) || $this->file->lastModified() > $this->entry['modified']) {
      $changes= $this->entryFrom(self::$descriptions->parse($this->file));
      $updated= time();
    }

    // Although the description file may not have changed, nested contents
    // may have, so process them unconditionally.
    yield from $this->contentsIn($files);

    if (isset($updated)) {
      $changes['locations']= yield new LookupLocationInfos($changes);
      $changes['published']= time();
      yield new PublishEntry($this->entry['slug'], $changes);
    }
  }

  /** @return string */
  public function toString() {
    return nameof($this).'<'.$this->name().'>';
  }
}