<?php namespace de\thekid\dialog\import;

use img\Image;
use img\io\{StreamReader, WebpStreamWriter, JpegStreamWriter, PngStreamWriter};
use io\File;

class ResizeTo {

  /** Creates a resizing target with a given size and image type */
  public function __construct(private int $size, private string $type) { }

  /**
   * Resizes the given source file to an output file of a given kind.
   * The resized file is stored in the same directory as the source
   * file with a file format specified by this target's extension.
   *
   * @throws io.IOException
   * @throws img.ImagingException
   */
  public function resize(File $source, string $kind, string $filename): File {
    $target= new File($source->path, $kind.'-'.$filename.'.'.$this->type);

    $image= Image::loadFrom(new StreamReader($source));
    $resized= Image::create(
      $this->size,
      (int)($image->height * ($this->size / $image->width)),
      Image::TRUECOLOR
    );
    $resized->resampleFrom($image);

    $resized->saveTo(match ($this->type) {
      'webp' => new WebpStreamWriter($target),
      'jpg'  => new JpegStreamWriter($target),
      'png'  => new PngStreamWriter($target),
    });
    return $target;
  }
}