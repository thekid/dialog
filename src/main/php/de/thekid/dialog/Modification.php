<?php namespace de\thekid\dialog;

use com\mongodb\Document;

/** @see https://www.mongodb.com/docs/manual/reference/command/findAndModify/ */
class Modification {

  public function __construct(private array<string, mixed> $result) { }

  /** Returns whether the operation created a new entry */
  public function created(): bool { return isset($this->result['lastErrorObject']['upserted']); }

  /** Returns the modified entry */
  public function entry(): Document { return new Document($this->result['value']); }
}