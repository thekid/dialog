<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\color\PriorityQueue;
use unittest\{Assert, Test};

class PriorityQueueTest {

  #[Test]
  public function can_create() {
    new PriorityQueue();
  }

  #[Test]
  public function initially_empty() {
    Assert::equals(0, new PriorityQueue()->size());
  }

  #[Test]
  public function size_after_pushing() {
    $queue= new PriorityQueue();
    $queue->push('Test');

    Assert::equals(1, $queue->size());
  }

  #[Test]
  public function pop_on_empty_queue() {
    Assert::null(new PriorityQueue()->pop());
  }

  #[Test]
  public function push_and_pop_roundtrip() {
    $queue= new PriorityQueue();
    $queue->push('Test');

    Assert::equals('Test', $queue->pop());
  }

  #[Test]
  public function pop_after_end() {
    $queue= new PriorityQueue();
    $queue->push('Test');
    $queue->pop();

    Assert::null($queue->pop());
  }

  #[Test]
  public function pop_returns_elements_according_to_their_sort_order() {
    $queue= new PriorityQueue();
    $queue->push('B');
    $queue->push('A');
    $queue->push('C');

    $elements= [];
    while (null !== $element= $queue->pop()) {
      $elements[]= $element;
    }
    Assert::equals(['C', 'B', 'A'], $elements);
  }

  #[Test]
  public function using_comparator() {
    $queue= new PriorityQueue()->comparing(fn($a, $b) => $b <=> $a);
    $queue->push('B');
    $queue->push('A');
    $queue->push('C');

    $elements= [];
    while (null !== $element= $queue->pop()) {
      $elements[]= $element;
    }
    Assert::equals(['A', 'B', 'C'], $elements);
  }
}