<?php namespace de\thekid\dialog\import;

readonly class Description {
  public function __construct(
    public array<string, mixed> $meta,
    public string $content
  ) { }
}
