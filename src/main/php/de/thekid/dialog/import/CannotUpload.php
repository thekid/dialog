<?php namespace de\thekid\dialog\import;

use io\OperationFailed;
use lang\Throwable;

class CannotUpload extends OperationFailed {

  /** Creates a new instance */
  public function __construct(string $file, string $target, string $reason, ?Throwable $cause= null) {
    parent::__construct($reason.' uploading '.$file.' to '.$target, $cause);
  }
}