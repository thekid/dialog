<?php namespace de\thekid\dialog\import;

use util\TimeZone;

class Description {
  public function __construct(
    public private(set) array<string, mixed> $meta,
    public private(set) string $content
  ) { }

  public function locations(string|TimeZone $timezone): iterable {
    $tz= $timezone instanceof TimeZone ? $timezone->name() : $timezone;
    foreach ($this->meta['locations'] ?? [$this->meta['location']] as $location) {
      $location['timezone']??= $tz;
      yield $location;
    }
  }
}
