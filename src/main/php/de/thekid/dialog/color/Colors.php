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
  const MAX_ITERATIONS= 1000;
  const FRACT_BY_POPULATIONS= 0.75;

  /** Creates a new instance with a given quality */
  public function __construct(private $quality= 10) { }

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

  /** Color quantization */
  private function quantize(Histogram $histogram, int $colors): PriorityQueue {
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

    return $queue;
  }

  /**
   * Returns palette for a given image
   * 
   * @throws lang.IllegalArgumentException if no palette can be computed
   */
  public function palette(Image $source, int $size= 10): array<Color> {

    // Area to examine = complete image
    $x= 0;
    $y= 0;
    $w= $source->getWidth();
    $h= $source->getHeight();

    // Create histogram
    $histogram= new Histogram();
    for ($i= 0, $n= $w * $h; $i < $n; $i+= $this->quality) {
      $color= $source->colorAt($x + ($i % $w), (int)($y + $i / $w));
      $histogram->add($color->red, $color->green, $color->blue);
    }

    // Check border case, e.g. for empty images
    if ($histogram->empty()) {
      throw new IllegalArgumentException('Cannot compute color palette');
    }

    // Quantize, then yield colors
    $queue= $this->quantize($histogram, $size);
    $colors= [];
    while ($box= $queue->pop()) {
      $colors[]= new Color(...$box->average());
    }
    return $colors;
  }
}