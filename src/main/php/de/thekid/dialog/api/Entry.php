<?php namespace de\thekid\dialog\api;

use lang\Value;
use util\{Date, Objects};

/** Represents an entry passed to the /entries API */
class Entry implements Value {
  private $attributes= [];

  public string $slug {
    get => $this->attributes['slug'];
    set { $this->attributes['slug']= $value; }
  }

  public Date $date {
    get => $this->attributes['date'];
    set { $this->attributes['date']= $value; }
  }

  public string $title {
    get => $this->attributes['title'];
    set { $this->attributes['title']= $value; }
  }

  public string $content {
    get => $this->attributes['content'];
    set { $this->attributes['content']= $value; }
  }

  public array<string, mixed> $is {
    get => $this->attributes['is'];
    set { $this->attributes['is']= $value; }
  }

  public ?string $parent {
    get => $this->attributes['parent'] ?? null;
    set { $this->attributes['parent']= $value; }
  }

  public array<string> $keywords {
    get => $this->attributes['keywords'] ?? [];
    set { $this->attributes['keywords']= $value; }
  }

  public array<array<mixed>> $locations {
    get => $this->attributes['locations'] ?? [];
    set { $this->attributes['locations']= $value; }
  }

  public array<string, mixed> $weather {
    get => $this->attributes['weather'];
    set { $this->attributes['weather']= $value; }
  }

  public ?Date $published {
    get => $this->attributes['published'] ?? null;
    set { $this->attributes['published']= $value; }
  }

  /** Returns all attributes as a map */
  public function attributes(): array<string, mixed> { return $this->attributes; }

  /** @return string */
  public function hashCode() { return 'E'.Objects::hashOf($this->attributes); }

  /** @return string */
  public function toString() { return nameof($this).'@'.Objects::stringOf($this->attributes); }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? Objects::compare($this->attributes, $value->attributes) : 1;
  }
}