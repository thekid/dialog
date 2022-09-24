<?php namespace de\thekid\dialog;

use com\mongodb\Document;

/** @test de.thekid.dialog.unittest.OptionalTest */
class Optional {

  /** Creates a new optional */
  public function __construct(private ?Document $result) { }

  /** Returns whether this optional value is present */
  public function present(): bool { return null !== $this->result; }

  /** Gets optional value. Might return NULL */
  public function get(): ?Document { return $this->result; }

  /** Gets optional value, alternatively executing a given function */
  public function or(function(): Document $function): Document { return $this->result ?? $function(); }
}