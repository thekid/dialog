<?php namespace de\thekid\dialog\processing;

use io\Folder;

/** @test de.thekid.dialog.unittest.FilesTest */
class Files {
  private $patterns= [];
  private $processed= null;

  /** Maps file extensions to a processing instance */
  public function matching(array<string> $extensions, Processing $processing): self {
    $this->patterns['/('.implode('|', array_map(preg_quote(...), $extensions)).')$/i']= $processing;
    $this->processed= null;
    return $this;
  }

  /** Returns a (cached) pattern to match all processed files */
  public function processed(): string {
    if (null !== $this->processed) return $this->processed;
    $prefixes= [];
    foreach ($this->patterns as $processing) {
      foreach ($processing->prefixes() as $prefix) {
        $prefixes[$prefix]= true;
      }
    }
    return $this->processed= '/^('.implode('|', array_keys($prefixes)).')-/';
  }

  /** Returns processing instance based on filename, or NULL */
  public function processing(string $filename): ?Processing {
    if (!preg_match($this->processed(), $filename)) {
      foreach ($this->patterns as $pattern => $processing) {
        if (preg_match($pattern, $filename)) return $processing;
      }
    }
    return null;
  }

  /** Yields files and their associated processing in a given folder */
  public function in(Folder $origin): iterable {
    foreach ($origin->entries() as $path) {
      if ($path->isFile() && ($processing= $this->processing($path->name()))) {
        yield $path->asFile() => $processing;
      }
    }
  }
}