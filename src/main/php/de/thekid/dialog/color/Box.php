<?php namespace de\thekid\dialog\color;

/** 3D color space box */
class Box {

  private function __construct(private array $components, private Histogram $histogram) { }

  /** Creates a box from a given histogram */
  public static function from(Histogram $histogram): self {
    $min= [256, 256, 256];
    $max= [-256, -256, -256];
    foreach ($histogram->colors() as $color) {
      foreach ($color as $i => $component) {
        if ($component < $min[$i]) $min[$i]= $component;
        if ($component > $max[$i]) $max[$i]= $component;
      }
    }
    return new self([$min[0], $max[0], $min[1], $max[1], $min[2], $max[2]], $histogram);
  }

  /** Creates a copy of this box */
  public function copy(): self {
    return new self($this->components, $this->histogram);
  }

  /** Volume */
  public function volume(): int {
    return (
      ($this->components[1] - $this->components[0] + 1) *
      ($this->components[3] - $this->components[2] + 1) *
      ($this->components[5] - $this->components[4] + 1)
    );
  }

  /** Total color count in this box */
  public function count(): int {
    [$rl, $ru, $gl, $gu, $bl, $bu]= $this->components;

    $n= 0;
    if ($this->volume() > $this->histogram->size()) {
      foreach ($this->histogram->colors() as $count => $c) {
        if ($c[0] >= $rl && $c[0] <= $ru && $c[1] >= $gl && $c[1] <= $gu && $c[2] >= $bl && $c[2] <= $bu) {
          $n+= $count;
        }
      }
    } else {
      for ($r= $rl; $r <= $ru; $r++) {
        for ($g= $gl; $g <= $gu; $g++) {
          for ($b= $bl; $b <= $bu; $b++) {
            $n+= $this->histogram->frequency($r, $g, $b);
          }
        }
      }
    }

    return $n;
  }

  /** Returns average color for this box */
  public function average(): array<int> {
    static $m= 1 << 3;

    [$rl, $ru, $gl, $gu, $bl, $bu]= $this->components;
    $n= $rs= $gs= $bs= 0;
    for ($r= $rl; $r <= $ru; $r++) {
      for ($g= $gl; $g <= $gu; $g++) {
        for ($b= $bl; $b <= $bu; $b++) {
          $h= $this->histogram->frequency($r, $g, $b);
          $n+= $h;
          $rs+= ($h * ($r + 0.5) * $m);
          $gs+= ($h * ($g + 0.5) * $m);
          $bs+= ($h * ($b + 0.5) * $m);
        }
      }
    }

    if ($n > 0) {
      return [(int)($rs / $n), (int)($gs / $n), (int)($bs / $n)];
    } else {
      return [
        min((int)($m * ($rl + $ru + 1) / 2), 255),
        min((int)($m * ($gl + $gu + 1) / 2), 255),
        min((int)($m * ($bl + $bu + 1) / 2), 255),
      ];
    }
  }

  /** Returns median boxes or NULL */
  public function median(): ?array<self> {
    if (1 === $this->count()) return [$this->copy()];

    // Cut using longest axis
    $r= $this->components[1] - $this->components[0];
    $g= $this->components[3] - $this->components[2];
    $b= $this->components[5] - $this->components[4];

    // Rearrange colors
    if ($r >= $g && $r >= $b) {
      [$c1, $c2]= [0, 1];
      [$il, $iu, $jl, $ju, $kl, $ku]= $this->components;
      $c= [&$i, &$j, &$k];
    } else if ($g >= $r && $g >= $b) {
      [$c1, $c2]= [2, 3];
      [$jl, $ju, $il, $iu, $kl, $ku]= $this->components;
      $c= [&$j, &$i, &$k];
    } else {
      [$c1, $c2]= [4, 5];
      [$jl, $ju, $kl, $ku, $il, $iu]= $this->components;
      $c= [&$j, &$k, &$i];
    }

    $total= 0;
    $partial= [];
    for ($i= $il; $i <= $iu; $i++) {
      $sum= 0;
      for ($j= $jl; $j <= $ju; $j++) {
        for ($k= $kl; $k <= $ku; $k++) {
          $sum+= $this->histogram->frequency(...$c);
        }
      }
      $total+= $sum;
      $partial[$i]= $total;
    }

    for ($i= $il, $h= $total / 2; $i <= $iu; $i++) {
      if ($partial[$i] <= $h) continue;

      $push= $this->copy();
      $add= $this->copy();

      // Choose the cut plane
      $l= $i - $il;
      $r= $iu - $i;
      $d= $l <= $r ? min($iu - 1, (int)($i + $r / 2)) : max($il, (int)($i - 1 - $l / 2));

      while (empty($partial[$d])) $d++;
      while ($partial[$d] >= $total && !empty($partial[$d - 1])) $d--;

      $push->components[$c2]= $d;
      $add->components[$c1]= $d + 1;
      return [$push, $add];
    }

    return null;
  }
}
