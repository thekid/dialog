<?php namespace de\thekid\dialog\processing;

use img\io\MetaDataReader;
use io\File;
use util\TimeZone;

class Images extends Processing {
  private static $UTC= TimeZone::getByName('UTC');
  private $meta= new MetaDataReader();

  public function meta(File $source): iterable {
    try {
      $meta= $this->meta->read($source->in());
      if ($exif= $meta?->exifData()) {
        yield 'width' => $exif->width;
        yield 'height' => $exif->height;
        yield 'dateTime' => $exif->dateTime?->toString('c', self::$UTC) ?? gmdate('c');
        yield 'make' => $exif->make;
        yield 'model' => $exif->model;
        yield 'apertureFNumber' => $exif->apertureFNumber;
        yield 'exposureTime' => $exif->exposureTime;
        yield 'isoSpeedRatings' => $exif->isoSpeedRatings;
        yield 'focalLength' => $exif->focalLength;
        yield 'flashUsed' => $exif->flashUsed();
      }
    } finally {
      $source->close();
    }
  }

  public function targets(File $source): iterable {
    foreach ($this->targets as $kind => $target) {
      yield $kind => $target->resize($source, $kind, $source->filename);
    }
  }
}