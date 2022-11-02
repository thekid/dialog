<?php namespace de\thekid\dialog;

use util\NoSuchElementException;
use web\Environment;

/** @test de.thekid.dialog.unittest.PreferencesTest */
class Preferences {
  private $sources;

  /** Creates a new Preferences instances from an environment and a config file name */
  public function __construct(Environment $env, string $config) {
    $this->sources= ['env' => fn($section, $name) => $env->variable(strtoupper("{$section}_{$name}"))];

    // If the properties file exists, use it as secondary source
    if ($prop= $env->properties($config, optional: true)) {
      $this->sources['config']= $prop->readString(...);
    }
  }

  /**
   * Gets a configuration value. Throws an exception if the value is
   * absent.
   *
   * @throws util.NoSuchElementException
   */
  public function get(string $section, string $name): string {
    foreach ($this->sources as $read) {
      if (null !== ($value= $read($section, $name))) return $value;
    }

    throw new NoSuchElementException(sprintf(
      'Missing configuration value <%s::%s> in %s',
      $section,
      $name,
      implode(', ', array_keys($this->sources)),
    ));
  }

  /**
   * Gets a configuration value. Returns a given default (or NULL) if
   * the value is absent.
   */
  public function optional(string $section, string $name, ?string $default= null): ?string {
    foreach ($this->sources as $read) {
      if (null !== ($value= $read($section, $name))) return $value;
    }
    return $default;
  }
}