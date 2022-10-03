<?php namespace de\thekid\dialog\import;

class Description {
  public function __construct(
    public readonly array<string, mixed> $meta,
    public readonly string $content
  ) { }
}
