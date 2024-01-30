<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\Preferences;
use test\{Assert, Before, Expect, Test, Values};
use util\NoSuchElementException;
use util\{Properties, RegisteredPropertySource};
use web\Environment;

class PreferencesTest {

  /**
   * Returns an environment for testing
   *
   * @param  ?[:string] $config
   * @return web.Environment
   */
  private function environment($config) {
    if ($config) {
      $p= new Properties('testing');
      $p->_data= $config;
      $sources= [new RegisteredPropertySource('config', $p)];
    } else {
      $sources= [];
    }

    return new class('test', '.', '.', $sources, [], []) extends Environment {
      private $variables;

      public function export($name, $value) {
        $this->variables[$name]= $value;
        return $this;
      }

      public function variable($name) {
        return $this->variables[$name] ?? null;
      }
    };
  }

  #[Test]
  public function can_create() {
    new Preferences($this->environment(null), 'config');
  }

  #[Test, Values(['get', 'optional'])]
  public function uses_environment($op) {
    $fixture= new Preferences($this->environment(null)->export('MONGO_URI', 'set'), 'config');

    Assert::equals('set', $fixture->{$op}('mongo', 'uri'));
  }

  #[Test, Values(['get', 'optional'])]
  public function uses_properties($op) {
    $fixture= new Preferences($this->environment(['mongo' => ['uri' => 'configured']]), 'config');

    Assert::equals('configured', $fixture->{$op}('mongo', 'uri'));
  }

  #[Test, Values(['get', 'optional'])]
  public function environment_has_precedence_over_configuration($op) {
    $fixture= new Preferences($this->environment(['mongo' => ['uri' => 'configured']])->export('MONGO_URI', 'set'), 'config');

    Assert::equals('set', $fixture->{$op}('mongo', 'uri'));
  }

  #[Test, Expect(class: NoSuchElementException::class, message: '/Missing.+mongo::uri/')]
  public function get_raises_exception_if_not_found() {
    $fixture= new Preferences($this->environment(null), 'config');

    $fixture->get('mongo', 'uri');
  }

  #[Test]
  public function optional_returns_null_if_not_found() {
    $fixture= new Preferences($this->environment(null), 'config');

    Assert::null($fixture->optional('mongo', 'uri'));
  }

  #[Test]
  public function optional_can_return_default_if_not_found() {
    $fixture= new Preferences($this->environment(null), 'config');

    Assert::equals('default', $fixture->optional('mongo', 'uri', 'default'));
  }
}