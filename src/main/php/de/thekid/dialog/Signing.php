<?php namespace de\thekid\dialog;

use text\hash\{Algorithm, Hashing};
use util\Secret;

/** @test de.thekid.dialog.unittest.SigningTest */
class Signing {
  private $tolerance= 10;

  public function __construct(private Secret $secret, private Algorithm $hashing= Hashing::sha256()) { }

  /** Uses a tolerance of a given amount of seconds */
  public function tolerating(int $seconds): self {
    $this->tolerance= $seconds;
    return $this;
  }

  /** Sign a given input string and return the signature parameter's value */
  public function sign(string $input, ?int $time= null): string {
    $time??= time();
    return "{$this->hashing->digest($input.$time.$this->secret->reveal())->hex()}.{$time}";
  }

  /** Verify a given signature is valid for a given input string */
  public function verify(string $input, string $signature, ?int $time= null): bool {
    sscanf($signature, '%[^.].%d', $digest, $from);
    $equals= $this->hashing->digest($input.$from.$this->secret->reveal())->equals($digest);
    $active= abs(($time ?? time()) - $from) <= $this->tolerance;
    return $equals && $active;
  }
}