<?php namespace de\thekid\dialog\processing;

use io\File;
use lang\Value;
use util\Objects;

class Data implements Value {

  /** Creates a new instance */
  public function __construct(private File $file, private int $offset, private int $length) { }

  /**
   * Returns stream for reading from. If the underlying file is not open,
   * opens it for reading and closes it when the returned stream is closed.
   *
   * @throws io.IOException
   */
  public function stream(): Stream {
    if ($this->file->isOpen()) {
      $close= false;
    } else {
      $close= true;
      $this->file->open(File::READ);
    }

    $this->file->seek($this->offset, SEEK_SET);
    return new Stream($this->file, $this->length, $close);
  }

  /** @return string */
  public function hashCode() {
    return $this->file->hashCode().$this->offset.':'.$this->length;
  }

  /** @return string */
  public function toString() {
    return sprintf(
      '%s<%s @%d +%d>',
      nameof($this),
      $this->file->toString(),
      $this->offset,
      $this->length
    );
  }

  /**
   * Comparison
   * 
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    if ($value instanceof self) {
      return Objects::compare(
        [$this->file, $this->offset, $this->length],
        [$value->file, $value->offset, $value->length]
      );
    }
    return 1;
  }
}