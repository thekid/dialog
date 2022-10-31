<?php namespace de\thekid\dialog\unittest;

use de\thekid\dialog\Preferences;
use unittest\{Assert, Before, Expect, Test, Values};
use util\NoSuchElementException;
use util\{Properties, RegisteredPropertySource};
use web\Environment;

class PreferencesTest {
  private $config, $environment;

  #[Before]
  public function environment() {
    $this->config= new class('testing') extends Properties {
      public function use($sections) { $this->_data= $sections; }
      public function exists(): bool { return null !== $this->_data; }
    };

    $source= new RegisteredPropertySource('config', $this->config);
    $this->environment= new class('test', '.', '.', [$source], [], []) extends Environment {
      private $variables;

      public function export($variables) {
        $this->variables= $variables;
        return $this;
      }

      public function variable($name) {
        return $this->variables[$name] ?? null;
      }
    };
  }

  #[Test]
  public function can_create() {
    new Preferences($this->environment, 'config');
  }

  #[Test, Values(['get', 'optional'])]
  public function uses_environment($op) {
    $this->config->use(null);
    $fixture= new Preferences($this->environment->export(['MONGO_URI' => 'set']), 'config');

    Assert::equals('set', $fixture->{$op}('mongo', 'uri'));
  }

  #[Test, Values(['get', 'optional'])]
  public function uses_properties($op) {
    $this->config->use(['mongo' => ['uri' => 'configured']]);
    $fixture= new Preferences($this->environment->export([]), 'config');

    Assert::equals('configured', $fixture->{$op}('mongo', 'uri'));
  }

  #[Test, Values(['get', 'optional'])]
  public function environment_has_precedence_over_configuration($op) {
    $this->config->use(['mongo' => ['uri' => 'configured']]);
    $fixture= new Preferences($this->environment->export(['MONGO_URI' => 'set']), 'config');

    Assert::equals('set', $fixture->{$op}('mongo', 'uri'));
  }

  #[Test, Expect(class: NoSuchElementException::class, withMessage: '/Missing.+mongo::uri/')]
  public function get_raises_exception_if_not_found() {
    $this->config->use(null);
    $fixture= new Preferences($this->environment->export([]), 'config');

    $fixture->get('mongo', 'uri');
  }

  #[Test]
  public function optional_returns_null_if_not_found() {
    $this->config->use(null);
    $fixture= new Preferences($this->environment->export([]), 'config');

    Assert::null($fixture->optional('mongo', 'uri'));
  }

  #[Test]
  public function optional_can_return_default_if_not_found() {
    $this->config->use(null);
    $fixture= new Preferences($this->environment->export([]), 'config');

    Assert::equals('default', $fixture->optional('mongo', 'uri', 'default'));
  }
}