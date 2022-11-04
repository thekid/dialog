<?php namespace de\thekid\dialog\color;

use img\{Color, Image};
use io\File;
use lang\IllegalArgumentException;

/**
 * Extracts color palette from a given image
 * 
 * Based on the fabulous work done by Lokesh Dhakar using color quantization
 * based on the MMCQ (modified median cut quantization) algorithm from the
 * Leptonica library.
 *
 * @see   https://github.com/lokesh/color-thief
 * @see   https://github.com/olivierlesnicki/quantize
 */
class Colors {
  const DOMINANT_PALETTE= 5;
  const MAX_ITERATIONS= 1000;
  const FRACT_BY_POPULATIONS= 0.75;

  /** Creates a new instance with a given quality */
  public function __construct(public int $quality= 10) { }

  /** Helper for quantize() */
  private function iterate(PriorityQueue $queue, float $target): void {
    $colors= $queue->size();
    $iteration= 0;
    do {
      if ($colors >= $target) return;

      $box= $queue->pop();
      if (0 === $box->count()) {
        $queue->push($box);
        continue;
      }

      [$push, $add]= $box->median();
      $queue->push($push);
      if ($add) {
        $queue->push($add);
        $colors++;
      }
    } while ($iteration++ < self::MAX_ITERATIONS);
  }

  /**
   * Color quantization from a given histogram
   *
   * @throws lang.IllegalArgumentException if no palette can be computed
   */
  private function quantize(Histogram $histogram, int $colors): array<Color> {

    // Check border case, e.g. for empty images
    if ($histogram->empty()) {
      throw new IllegalArgumentException('Cannot quantize using an empty histogram');
    }

    $queue= new PriorityQueue();
    $queue->push(Box::from($histogram));

    // First set of colors, sorted by population
    $this->iterate(
      $queue->comparing(fn($a, $b) => $a->count() <=> $b->count()),
      self::FRACT_BY_POPULATIONS * $colors
    );

    // Next set - generate the median cuts using the (npix * vol) sorting.
    $this->iterate(
      $queue->comparing(fn($a, $b) => ($a->count() * $a->volume()) <=> ($b->count() * $b->volume())),
      $colors
    );

    $colors= [];
    while ($box= $queue->pop()) {
      $colors[]= new Color(...$box->average());
    }
    return $colors;
  }

  /**
   * Returns histogram for a given image. Uses complete image by default
   * but may be given a 4-element array as follows: `[x, y, w, h]`.
   *
   * The computed histogram for an image can be recycled to derive palette
   * and dominant color.
   */
  public function histogram(Image $source, ?array $area= null): Histogram {
    [$x, $y, $w, $h]= $area ?? [0, 0, ...$source->getDimensions()];

    $histogram= new Histogram();
    for ($i= 0, $n= $w * $h; $i < $n; $i+= $this->quality) {
      $color= $source->colorAt($x + ($i % $w), (int)($y + $i / $w));
      $histogram->add($color->red, $color->green, $color->blue);
    }
    return $histogram;
  }

  /**
   * Returns the dominant color in an image or histogram
   *
   * @throws lang.IllegalArgumentException if palette is empty
   */
  public function color(Image|Histogram $source): ?Color {
    return current($this->quantize(
      $source instanceof Histogram ? $source : $this->histogram($source),
      self::DOMINANT_PALETTE
    ));
  }

  /**
   * Returns a palette with a given size for an image or histogram
   *
   * @throws lang.IllegalArgumentException if palette is empty
   */
  public function palette(Image|Histogram $source, int $size= 10): array<Color> {
    return $this->quantize($source instanceof Histogram ? $source : $this->histogram($source), $size);
  }
}