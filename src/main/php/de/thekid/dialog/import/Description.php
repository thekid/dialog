<?php namespace de\thekid\dialog\import;

class Description {
  public function __construct(
    public private(set) array<string, mixed> $meta,
    public private(set) string $content
  ) { }
}
