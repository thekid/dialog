<?php namespace de\thekid\dialog\processing;

use io\File;
use lang\{Process, IllegalStateException};

class Videos extends Processing {

  public function __construct(private string $executable= 'ffmpeg') { }

  /** Executes a given external command and returns its exit code */
  private function execute(string $command, array<string> $args): void {
    $p= new Process($command, $args, null, null, [STDIN, STDOUT, STDERR]);
    if (0 === ($r= $p->close())) return;

    throw new IllegalStateException($p->getCommandLine().' exited with exit code '.$r);
  }

  public function meta(File $source): iterable {
    return [];
  }

  public function targets(File $source): iterable {

    // 1. Convert to web-optimized H.264 video, scaling to a width of 1920 pixels
    $video= new File($source->path, 'video-'.$source->filename.'.mp4');
    if (!$video->exists() || $source->lastModified() > $video->lastModified()) {
      $this->execute($this->executable, [
        '-y',       // Overwrite files without asking
        '-i', $source->getURI(),
        '-vcodec', 'libx264',
        '-vf', 'scale=1920:-1',
        '-acodec', 'aac',
        '-g', '30', // Group of picture (GOP)
        $video->getURI(),
      ]);
    }
    yield 'video' => $video;

    // 2. Extract screenshot and preview image using ffmpeg
    $screen= new File($source->path, 'screen-'.$source->filename.'.jpg');
    if (!$screen->exists() || $source->lastModified() > $screen->lastModified()) {
      $this->execute($this->executable, [
        '-y',
        '-i', $source->getURI(),
        '-ss', '00:00:03',
        '-vsync', 'vfr',
        '-frames:v', '1',
        '-q:v', '1',
        '-qscale:v', '1',
        $screen->getURI(),
      ]);
    }

    // 3. Convert and resize screenshot JPEG
    foreach ($this->targets as $kind => $target) {
      yield $kind => $target->resize($screen, $kind, $source->filename);
    }
  }
}