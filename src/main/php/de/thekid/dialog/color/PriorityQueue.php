<?php namespace de\thekid\dialog\color;

/** @test de.thekid.dialog.unittest.PriorityQueueTest */
class PriorityQueue {
  private $elements= [];
  private $sorted= true;
  private $comparator= null;

  public function comparing(function(mixed, mixed): int $comparator): self {
    $this->comparator= $comparator;
    return $this;
  }

  /** Returns size */
  public function size(): int {
    return sizeof($this->elements);
  }

  /** Pushes an element */
  public function push($element): void {
    $this->elements[]= $element;
    $this->sorted= false;
  }

  /** Pops an element */
  public function pop() {
    if (!$this->sorted) {
      $this->comparator ? usort($this->elements, $this->comparator) : sort($this->elements);
      $this->sorted= true;
    }
    return array_pop($this->elements);
  }
}
