<?php namespace de\thekid\dialog\processing;

use io\File;
use io\streams\InputStream;

class Stream implements InputStream {
  private $pointer= 0;

  /** Creates a new instance */
  public function __construct(private File $file, private int $length, private bool $close) { }

  /**
   * Returns how many byts are available
   *
   * @return int
   */
  public function available() {
    return $this->length - $this->pointer;
  }

  /**
   * Reads given number of bytes
   *
   * @param  int $bytes
   * @return string
   */
  public function read($bytes= 8192) {
    $chunk= $this->file->read($bytes);
    $this->pointer+= strlen($chunk);
    return $chunk;
  }

  /**
   * Closes this stream
   *
   * @return void
   */
  public function close() {
    $this->close && $this->file->close();
  }
}