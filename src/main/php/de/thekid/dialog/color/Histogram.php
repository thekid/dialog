<?php namespace de\thekid\dialog\color;

class Histogram {
  const THRESHOLD = 250;
  private $colors= [];

  /**
   * Adds a given color from its components. Ignores colors too close to
   * all-white (above the constant threshold).
   */
  public function add(int $r, int $g, int $b): void {

    // Ignore colors too close to all-white
    if ($r > self::THRESHOLD && $g > self::THRESHOLD && $b > self::THRESHOLD) return;

    $i= (($r >> 3) << 10) | (($g >> 3) << 5) | ($b >> 3);
    $this->colors[$i]= ($this->colors[$i] ?? 0) + 1;
  }

  /** Returns whether this histogram is empty */
  public function empty(): bool {
    return empty($this->colors);
  }

  /** Returns this histogram's size */
  public function size(): int {
    return sizeof($this->colors);
  }

  /** Returns the frequency of a given color */
  public function frequency(int $r, int $g, int $b): int {
    return $this->colors[($r << 10) | ($g << 5) | $b] ?? 0;
  }

  public function colors() {
    foreach ($this->colors as $i => $count) {
      yield $count => [($i >> 10) & 31, ($i >> 5) & 31, $i & 31];
    }
  }
}
