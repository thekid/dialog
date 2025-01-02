<?php namespace de\thekid\dialog\processing;

use io\File;
use util\TimeZone;

abstract class Processing {
  protected const DATEFORMAT= 'd.m.Y H:i';
  protected $targets= [];

  /** Returns processing kind */
  public abstract function kind(): string;

  /** Returns prefixes used by the targets */
  public function prefixes(): array<string> { return array_keys($this->targets); }

  /**
   * Adds a conversion target with a given prefix and conversion target.
   * Fluent interface.
   */
  public function targeting(string $prefix, ResizeTo $target): self {
    $this->targets[$prefix]= $target;
    return $this;
  }

  /**
   * Extracts meta data from given source file and returns it as map of
   * key/value pairs. Returns an empty array if no meta data is found.
   */
  public abstract function meta(File $source): array<string, mixed>;

  /**
   * Processes source file, yielding target files. Takes an optional file
   * name, using the source file name if omitted.
   */
  public abstract function targets(File $source, ?string $filename= null): iterable;
}