<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\Signing;
use text\hash\Hashing;
use unittest\{Assert, Before, Test};
use util\Secret;

class SigningTest {
  private const TEST_TIME= 1668365788;
  private $secret= new Secret('test');

  #[Test]
  public function can_create() {
    new Signing($this->secret);
  }

  #[Test]
  public function can_create_with_hashing() {
    new Signing($this->secret, Hashing::sha256());
  }

  #[Test]
  public function sign_and_verify() {
    $s= new Signing($this->secret);
    $query= 'target=test';

    $signature= $s->sign($query);
    Assert::true($s->verify($query, $signature));
  }

  #[Test, Values([-3600, -1, 1, 3600])]
  public function cannot_verify_links_after_expiration_window($window) {
    $s= new Signing($this->secret)->tolerating(0);
    $query= 'target=test';

    $signature= $s->sign($query, time: self::TEST_TIME);
    Assert::false($s->verify($query, $signature, time: self::TEST_TIME + $window));
  }

  #[Test, Values([-10, -1, 0, 1, 10])]
  public function verify_links_within_expiration_window($window) {
    $s= new Signing($this->secret)->tolerating(10);
    $query= 'target=test';

    $signature= $s->sign($query, time: self::TEST_TIME);
    Assert::true($s->verify($query, $signature, time: self::TEST_TIME + $window));
  }
}