<?php namespace de\thekid\dialog\color;

class PriorityQueue {
  private $elements= [];
  private $sorted= true;
  private $comparator= null;

  public function comparing(function(mixed, mixed): int $comparator): self {
    $this->comparator= $comparator;
    return $this;
  }

  public function size(): int {
    return sizeof($this->elements);
  }

  public function push($element): void {
    $this->elements[]= $element;
    $this->sorted= false;
  }

  public function pop() {
    if (!$this->sorted && $this->comparator) {
      usort($this->elements, $this->comparator);
      $this->sorted= true;
    }
    return array_pop($this->elements);
  }
}
