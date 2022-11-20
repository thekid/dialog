<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\processing\{Files, Images};
use unittest\{Assert, Expect, Test, Values};

class FilesTest {

  #[Test]
  public function can_create() {
    new Files();
  }

  #[Test, Values(['test.jpg', 'IMG_1234.JPG', '20221119-iOS.jpeg'])]
  public function matching_jpeg_files($filename) {
    $processing= new Images();
    $fixture= new Files()->matching(['.jpg', '.jpeg'], $processing);

    Assert::equals($processing, $fixture->processing($filename));
  }

  #[Test, Values(['test-jpg', 'IMG_1234JPG', 'jpeg', '.jpeg-file'])]
  public function unmatched_jpeg_files($filename) {
    $processing= new Images();
    $fixture= new Files()->matching(['.jpg', '.jpeg'], $processing);

    Assert::null($fixture->processing($filename));
  }
}