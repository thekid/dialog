<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\Storage;
use io\Folder;
use unittest\{Assert, Test};

class StorageTest {
  private $base= new Folder('.');

  #[Test]
  public function can_create() {
    new Storage($this->base);
  }

  #[Test]
  public function folder() {
    Assert::equals(
      new Folder($this->base, 'image', 'test'),
      new Storage($this->base)->folder('test')
    );
  }

  #[Test]
  public function compose_folder() {
    Assert::equals(
      new Folder($this->base, 'image', '@uploads', 'test'),
      new Storage($this->base)->folder('@uploads', 'test')
    );
  }
}