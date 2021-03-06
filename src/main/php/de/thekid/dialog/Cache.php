<?php namespace de\thekid\dialog;

use util\TimeSpan;

/** Simple in-memory cache */
class Cache {
  private $values= [];

  public function __construct(private TimeSpan $expire) { }

  /**
   * Register a variable with a given name and fetch function
   *
   * @param  string $name
   * @param  function(): var $func
   * @return void
   */
  public function register($name, $func) {
    $this->values[$name]= [
      'value' => null,
      'time'  => null,
      'fetch' => $func
    ];
  }

  /**
   * Gets the value of a given variable
   *
   * @param  string $name
   * @param  int $when
   * @return void
   */
  public function value($name, $when= null) {
    $v= &$this->values[$name];
    $t= $when ?? time();
    if ($t > $v['time']) {
      $v['value']= $v['fetch']();
      $v['time']= $t + $this->expire->getSeconds();
    }
    return $v['value'];
  }
}