<?php namespace de\thekid\dialog\processing;

/** @test de.thekid.dialog.unittest.FilesTest */
class Files {
  private $patterns= [];

  /** Maps file extensions to a processing instance */
  public function matching(array<string> $extensions, Processing $processing): self {
    $this->patterns['/('.implode('|', array_map(preg_quote(...), $extensions)).')$/i']= $processing;
    return $this;
  }

  /** Returns processing instance based on filename, or NULL */
  public function processing(string $filename): ?Processing {
    foreach ($this->patterns as $pattern => $processing) {
      if (preg_match($pattern, $filename)) return $processing;
    }
    return null;
  }
}