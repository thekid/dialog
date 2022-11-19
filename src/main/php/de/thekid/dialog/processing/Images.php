<?php namespace de\thekid\dialog\processing;

use img\io\MetaDataReader;
use io\File;
use util\TimeZone;

class Images extends Processing {
  private static $UTC= TimeZone::getByName('UTC');
  private $meta= new MetaDataReader();

  public function meta(File $source): array<string, mixed> {
    $r= [];
    try {
      $meta= $this->meta->read($source->in());

      // Check for EXIF data
      if ($exif= $meta?->exifData()) {
        $r+= [
          'width'           => $exif->width,
          'height'          => $exif->height,
          'dateTime'        => $exif->dateTime?->toString('c', self::$UTC) ?? gmdate('c'),
          'make'            => $exif->make,
          'model'           => $exif->model,
          'apertureFNumber' => $exif->apertureFNumber,
          'exposureTime'    => $exif->exposureTime,
          'isoSpeedRatings' => $exif->isoSpeedRatings,
          'focalLength'     => $exif->focalLength,
          'flashUsed'       => $exif->flashUsed(),
        ];
      }
      return $r;
    } finally {
      $source->close();
    }
  }

  public function targets(File $source, ?string $filename= null): iterable {
    $filename??= $source->filename;
    foreach ($this->targets as $kind => $target) {
      yield $kind => $target->resize($source, $kind, $filename);
    }
  }
}