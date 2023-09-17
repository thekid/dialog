<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\processing\Videos;
use io\File;
use lang\Environment;
use test\{Assert, After, Test, Values};

class VideoMetaTest {
  private $videos= new Videos();
  private $tempDir= Environment::tempDir();
  private $cleanup= [];

  /** Creates a single atom and returns its binary representation */
  private function atom(string $name, string|array<string> $contained): string {
    $data= is_array($contained) ? implode('', $contained) : $contained;
    return pack('Na4a*', strlen($data) + 8, $name, $data);
  }

  /** Creates a `keys` atom */
  private function keys(string $ns, array<string> $names) {
    $data= '';
    foreach ($names as $name) {
      $data.= $this->atom($ns, $name);
    }
    return $this->atom('keys', pack('cc3Na*', 0, 0, 0, 0, sizeof($names), $data));
  }

  /** Creates an `ilst` atom with a given component kind */
  private function list(string $kind, array<string> $values) {
    $data= '';
    $index= 1;
    foreach ($values as $value) {
      $data.= $this->atom(pack('N', $index++), $this->atom($kind, pack('Na4a*', 1, "\0\0\0\0", $value)));
    }
    return $this->atom('ilst', $data);
  }

  /** Creates a file with the given atoms which will be deleted after the tests finish */
  private function file(string... $atoms): File {
    $f= new File($this->tempDir, uniqid().'.mp4');
    $f->open(File::WRITE);
    foreach ($atoms as $atom) {
      $f->write($atom);
    }
    $f->close();

    $this->cleanup[]= $f;
    return $f;
  }

  #[After]
  public function cleanup() {
    foreach ($this->cleanup as $file) {
      $file->unlink();
    }
  }

  #[Test]
  public function empty_moov_atoms() {
    $meta= $this->videos->meta($this->file($this->atom('moov', [])));
    Assert::equals([], $meta);
  }

  #[Test]
  public function creation_date_from_mvhd() {
    $meta= $this->videos->meta($this->file($this->atom('moov', [
      $this->atom('mvhd', pack('cc3NNNN', 0, 0, 0, 0, 3777782036, 3777782037, 1000, 3500)),
    ])));
    Assert::equals('2023-09-17T07:53:56+00:00', $meta['dateTime']);
  }

  #[Test]
  public function duration_from_mvhd() {
    $meta= $this->videos->meta($this->file($this->atom('moov', [
      $this->atom('mvhd', pack('cc3NNNN', 0, 0, 0, 0, 3777782036, 3777782037, 1000, 3500)),
    ])));
    Assert::equals(3.5, $meta['duration']);
  }

  #[Test]
  public function ios_creation_date_prefererred() {
    $meta= $this->videos->meta($this->file($this->atom('moov', [
      $this->atom('mvhd', pack('cc3NNNN', 0, 0, 0, 0, 3777782036, 3777782037, 1000, 3500)),
      $this->atom('meta', [
        $this->keys('mdta', ['com.apple.quicktime.creationdate']),
        $this->list('data', ['2023-09-17T08:33:56+00:00']),
      ])
    ])));
    Assert::equals('2023-09-17T08:33:56+00:00', $meta['dateTime']);
  }

  #[Test, Values(['com.apple.quicktime.make', 'com.android.manufacturer'])]
  public function make($key) {
    $meta= $this->videos->meta($this->file($this->atom('moov', [
      $this->atom('mvhd', pack('cc3NNNN', 0, 0, 0, 0, 3777782036, 3777782037, 1000, 3500)),
      $this->atom('meta', [
        $this->keys('mdta', [$key]),
        $this->list('data', ['Test']),
      ])
    ])));
    Assert::equals('Test', $meta['make']);
  }

  #[Test, Values(['com.apple.quicktime.model', 'com.android.model'])]
  public function model($key) {
    $meta= $this->videos->meta($this->file($this->atom('moov', [
      $this->atom('mvhd', pack('cc3NNNN', 0, 0, 0, 0, 3777782036, 3777782037, 1000, 3500)),
      $this->atom('meta', [
        $this->keys('mdta', [$key]),
        $this->list('data', ['Test']),
      ])
    ])));
    Assert::equals('Test', $meta['model']);
  }
}