<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\color\PriorityQueue;
use unittest\{Assert, Test};

class PriorityQueueTest {

  /** Returns all elements in a given queue */
  private function all(PriorityQueue<string> $queue): array<string> {
    $elements= [];
    while (null !== $element= $queue->pop()) {
      $elements[]= $element;
    }
    return $elements;
  }

  #[Test]
  public function can_create() {
    new PriorityQueue<string>();
  }

  #[Test]
  public function initially_empty() {
    Assert::equals(0, new PriorityQueue<string>()->size());
  }

  #[Test]
  public function size_after_pushing() {
    $queue= new PriorityQueue<string>();
    $queue->push('Test');

    Assert::equals(1, $queue->size());
  }

  #[Test]
  public function pop_on_empty_queue() {
    Assert::null(new PriorityQueue<string>()->pop());
  }

  #[Test]
  public function push_and_pop_roundtrip() {
    $queue= new PriorityQueue<string>();
    $queue->push('Test');

    Assert::equals('Test', $queue->pop());
  }

  #[Test]
  public function pop_after_end() {
    $queue= new PriorityQueue<string>();
    $queue->push('Test');
    $queue->pop();

    Assert::null($queue->pop());
  }

  #[Test]
  public function pop_returns_elements_according_to_their_sort_order() {
    $queue= new PriorityQueue<string>();
    $queue->push('B');
    $queue->push('A');
    $queue->push('C');

    Assert::equals(['C', 'B', 'A'], $this->all($queue));
  }

  #[Test]
  public function using_comparator() {
    $queue= new PriorityQueue<string>()->comparing(fn(string $a, string $b): int => $b <=> $a);
    $queue->push('B');
    $queue->push('A');
    $queue->push('C');

    Assert::equals(['A', 'B', 'C'], $this->all($queue));
  }

  #[Test]
  public function using_default_comparator() {
    $queue= new PriorityQueue<string>()->comparing(null);
    $queue->push('B');
    $queue->push('A');
    $queue->push('C');

    Assert::equals(['C', 'B', 'A'], $this->all($queue));
  }
}