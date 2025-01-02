<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\processing\{Files, Images, ResizeTo, Processing};
use test\{Assert, Expect, Test, Values};

class FilesTest {

  /** Returns a fixture with a given processing instance */
  private function fixtureWith(Processing $processing): Files {
    return new Files()->matching(['.jpg', '.jpeg'], $processing);
  }

  #[Test]
  public function can_create() {
    new Files();
  }

  #[Test, Values(['test.jpg', 'IMG_1234.JPG', '20221119-iOS.jpeg'])]
  public function matching_jpeg_files($filename) {
    $processing= new Images();
    Assert::equals($processing, $this->fixtureWith($processing)->processing($filename));
  }

  #[Test, Values(['test-jpg', 'IMG_1234JPG', 'jpeg', '.jpeg-file'])]
  public function unmatched_jpeg_files($filename) {
    $processing= new Images();
    Assert::null($this->fixtureWith($processing)->processing($filename));
  }

  #[Test]
  public function processed_pattern() {
    $processing= new Images()->targeting('preview', new ResizeTo(720, 'jpg'));
    Assert::equals('/^(preview)-/', $this->fixtureWith($processing)->processed());
  }
}