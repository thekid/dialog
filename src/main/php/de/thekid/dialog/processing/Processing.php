<?php namespace de\thekid\dialog\processing;

use io\File;

abstract class Processing {
  protected $targets= [];

  /** Returns processing kind */
  public abstract function kind(): string;

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