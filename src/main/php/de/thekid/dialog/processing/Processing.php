<?php namespace de\thekid\dialog\processing;

use io\File;

abstract class Processing {
  protected $targets= [];

  public function targeting(string $prefix, ResizeTo $target): self {
    $this->targets[$prefix]= $target;
    return $this;
  }

  public abstract function meta(File $source): iterable;

  public abstract function targets(File $source): iterable;
}