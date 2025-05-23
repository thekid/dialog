<?php namespace de\thekid\dialog\import;

use de\thekid\dialog\processing\Files;
use io\{File, Folder};
use lang\{IllegalArgumentException, Value};
use util\Comparison;

abstract class Source implements Value {
  use Comparison;

  private static $descriptions= new Descriptions();
  private $name;

  /** Creates a new source from a given folder and file, optionally passing an existing entry */
  public function __construct(
    protected Folder $origin,
    protected File $file,
    protected ?array<string, mixed> $entry= null
  ) {
    $this->name= $this->origin->dirname;
  }

  /** Creates a source from a given origin folder */
  public static function in(string|Folder $origin): self {
    static $implementations= [
      'content.md' => new Content(...),
      'journey.md' => new Journey(...),
      'cover.md'   => new Cover(...),
    ];

    foreach ($implementations as $source => $new) {
      $file= new File($origin, $source);
      if ($file->exists()) return $new($origin instanceof Folder ? $origin : new Folder($origin), $file);
    }

    throw new IllegalArgumentException(sprintf(
      'Cannot locate any of [%s] in %s',
      implode(', ', array_keys($implementations)),
      $origin
    ));
  }

  /** Returns this source's name */
  public function name(): string { return $this->name; }

  /** Returns this source's parent name, if any */
  public function parent(): ?string { return strstr($this->name(), '/', true) ?: null; }

  /** Sets a parent for this source */
  public function nestedIn(string $parent): self { $this->name= $parent.'/'.$this->name; return $this; }

  /** Returns this source's origin */
  public function origin(): Folder { return $this->origin; }

  /** Yields all the media files in this source */
  protected function mediaIn(Files $files): iterable {
    $updated= false;
    $images= [];
    foreach ($this->entry['images'] ?? [] as $image) {
      $images[$image['name']]= $image;
    }

    foreach ($files->in($this->origin) as $file => $processing) {
      $name= $file->filename;
      if (!isset($images[$name]) || $file->lastModified() > $images[$name]['modified']) {
        $this->entry['images'][$name]= yield new UploadMedia($this->entry['slug'], $file, $processing);
        $updated= true;
      }
      unset($images[$name]);
    }

    foreach ($images as $rest) {
      yield new DeleteMedia($this->entry['slug'], $rest['name']);
      $updated= true;
    }
    return $updated;
  }

  /** Returns an entry from the given description */
  public abstract function entryFrom(Description $description): array<string, mixed>;

  /** Yields contents of this source */
  public abstract function contentsIn(Files $files): iterable;

  /** Yields tasks to synchronize this source */
  public function synchronize(Files $files): iterable {
    $this->entry??= yield new FetchEntry($this->name());
    if (!isset($this->entry['modified']) || $this->file->lastModified() > $this->entry['modified']) {
      $changes= $this->entryFrom(self::$descriptions->parse($this->file));
      $this->entry= $changes + ($this->entry ?? []);
      $updated= true;
    } else {
      $changes= [];
      $updated= false;
    }

    // Although the description file may not have changed, nested contents
    // may have, so process them unconditionally.
    $updated|= yield from $this->contentsIn($files);

    if ($updated) {
      $changes['locations']= $this->entry['locations']= yield new LookupLocationInfos($this->entry);
      $changes['weather']= $this->entry['weather']= yield new LookupWeather($this->entry);
      yield new PublishEntry($this->entry['slug'], $changes + ['published' => time()]);
    }
    return $updated;
  }

  /** @return string */
  public function toString() {
    return nameof($this).'<'.$this->name().'>';
  }
}