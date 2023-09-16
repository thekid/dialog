<?php namespace de\thekid\dialog\processing;

use io\File;
use util\Bytes;

/** @see https://developer.apple.com/documentation/quicktime-file-format/movie_atoms */
class Atoms {
  private $parsers;

  public function __construct() {
    $this->parsers= [
      'moov.mvhd' => function($f, $atom) {

        // See https://developer.apple.com/documentation/quicktime-file-format/movie_header_atom
        return unpack('cversion/c3flags/Ncreated/Nmodified/Nscale/Nduration', $f->read(20));
      },
      'moov.meta.keys' => function($f, $atom) {
        $r= [];
        $info= unpack('cversion/c3flags/Ncount', $f->read(8));
        for ($i= 0; $i < $info['count']; $i++) {
          $entry= unpack('Nsize/a4ns', $f->read(8));
          $r[$i + 1]= $entry['ns'].':'.$f->read($entry['size'] - 8);
        }
        return $r;
      },
      'moov.meta.ilst' => function($f, $atom) {
        $r= [];
        foreach ($this->atoms($f, $atom) as $child) {
          $index= unpack('N', $child['name'])[1];
          $r[$index]= [];
          foreach ($this->atoms($f, $child) as $data) { 
            $entry= unpack('Ntype/a4locale', $f->read(8));
            $bytes= $f->read($data['length'] - 16);

            // See https://developer.apple.com/library/archive/documentation/QuickTime/QTFF/Metadata/Metadata.html#//apple_ref/doc/uid/TP40000939-CH1-SW34
            $r[$index][]= match ($entry['type']) {
              1       => $bytes,    // yield utf-8 as-is
              default => new Bytes($bytes)
            };
          }
        }
        return $r;
      },
      "moov.udta.\251xyz" => function($f, $atom) {
        $entry= unpack('nsize/ntype', $f->read(4));
        preg_match_all('/[+-][0-9.]+/', $f->read($entry['size'] - 1), $c);
        return $c[0];
      },

      // TODO: Convert to io.streams.InputStream instance
      // 'moov.udta.mcvr' => function($f, $atom) {
      //   $out= new File('cover.jpeg');
      //   $out->open(File::WRITE);
      //   $out->write($f->read($atom['length'] - 8));
      //   $out->close();
      //   return $out;
      // },
    ];
  }

  private function atom($f, $base= null) {
    $atom= ['offset' => $f->tell(), 'path' => $base] + unpack('Nlength/a4name', $f->read(8));
    if (0 === $atom['length']) {
      $atom['length']= $f->size() - $f->tell();
    } else if (1 === $atom['length']) {
      $atom['length']= unpack('J', $f->read(8))[1];
    }

    if ($parser= ($this->parsers[$base.$atom['name']] ?? $this->parsers[$base.'*'] ?? null)) {
      $atom['value']= $parser($f, $atom);
    }

    return $atom;
  }

  private function atoms($f, $parent) {
    static $CONTAINERS= ['moov' => 1, 'udta' => 1, 'ilst' => 1, 'meta' => 1];

    $limit= $parent ? $parent['offset'] + $parent['length'] : $f->size();
    $base= $parent ? $parent['path'].$parent['name'].'.' : '';

    while (($offset= $f->tell()) < $limit) {
      $atom= $this->atom($f, $base);
      yield $atom['path'].$atom['name'] => $atom;

      $end= $atom['offset'] + $atom['length'];
      if (isset($CONTAINERS[$atom['name']])) {
        yield from $this->atoms($f, $atom);
      }

      $f->seek($end, SEEK_SET);
    }
  }

  /** Yields atoms in a given file */
  public function in(File $f): iterable {
    if ($f->isOpen()) {
      yield from $this->atoms($f, null);
    } else {
      $f->open(File::READ);
      try {
        yield from $this->atoms($f, null);
      } finally {
        $f->close();
      }
    }
  }
}