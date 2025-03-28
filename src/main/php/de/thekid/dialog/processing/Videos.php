<?php namespace de\thekid\dialog\processing;

use io\File;
use lang\{Process, IllegalStateException};
use util\Date;

/**
 * Processes videos with the help of `ffmpeg`, extracting meta information
 * from MP4, MOV and MPEG files by parsing their header and meta data atoms.
 *
 * @see  https://ffmpeg.org/
 * @test de.thekid.dialog.unittest.VideoMetaTest
 */
class Videos extends Processing {
  private $atoms= new Atoms();

  public function __construct(private string $executable= 'ffmpeg') { }

  /** Returns processing kind */
  public function kind(): string { return 'video'; }

  /** Returns prefixes used by the targets */
  public function prefixes(): array<string> { return [...parent::prefixes(), 'video', 'screen']; }

  /** Executes a given external command and returns its exit code */
  private function execute(string $command, array<string> $args): void {
    $p= new Process($command, $args, null, null, [STDIN, STDOUT, STDERR]);
    if (0 === ($r= $p->close())) return;

    throw new IllegalStateException($p->getCommandLine().' exited with exit code '.$r);
  }

  public function meta(File $source): array<string, mixed> {
    static $mdta= [
      'mdta:com.apple.quicktime.make'  => 'make',
      'mdta:com.apple.quicktime.model' => 'model',
      'mdta:com.android.manufacturer'  => 'make',
      'mdta:com.android.model'         => 'model',
    ];

    if (preg_match('/\.(mov|mp4|mpeg)$/i', $source->getFileName())) {
      $meta= [];
      foreach ($this->atoms->in($source) as $name => $atom) {
        if ('moov.meta.keys' === $name) {
          $keys= $atom['value'];
        } else if ('moov.meta.ilst' === $name) {
          $meta+= array_combine($keys, $atom['value']);
        } else if ('moov.mvhd' === $name) {
          $meta['mvhd']= $atom['value'];
        }
      }

      // Normalize meta data from iOS and Android devices
      $r= [];
      foreach ($meta as $key => $value) {
        if ($mapped= $mdta[$key] ?? null) {
          $r[$mapped]= $value[0];
        }
      }

      // Prefer original creation date from iOS, converting it to local time
      if ($date= $meta['mdta:com.apple.quicktime.creationdate'][0] ?? null) {
        $r['dateTime']= new Date(preg_replace('/[+-][0-9]{4}$/', '', $date))->toString(self::DATEFORMAT);
      }

      // Aggregate information from movie header: Duration and creation time
      // Time info is the number of seconds since 1904-01-01 00:00:00 UTC
      if (isset($meta['mvhd'])) {
        $r['duration']= round($meta['mvhd']['duration'] / $meta['mvhd']['scale'], 3);
        $r['dateTime']??= new Date($meta['mvhd']['created'] - 2082844800)->toString(self::DATEFORMAT);
      }

      return $r;
    }
    return [];
  }

  public function targets(File $source, ?string $filename= null): iterable {
    $filename??= $source->filename;

    // 1. Convert to web-optimized H.264 video, scaling to a width of 1920 pixels
    $video= new File($source->path, 'video-'.$filename.'.mp4');
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
    $screen= new File($source->path, 'screen-'.$filename.'.jpg');
    if (!$screen->exists() || $source->lastModified() > $screen->lastModified()) {
      $this->execute($this->executable, [
        '-y',
        '-i', $source->getURI(),
        '-ss', '00:00:03',
        '-vsync', 'vfr',
        '-update', 'true',
        '-frames:v', '1',
        '-q:v', '1',
        '-qscale:v', '1',
        $screen->getURI(),
      ]);
    }

    // 3. Convert and resize screenshot JPEG
    foreach ($this->targets as $kind => $target) {
      yield $kind => $target->resize($screen, $kind, $filename);
    }
  }
}