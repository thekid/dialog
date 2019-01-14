<?php namespace de\thekid\dialog;

use util\TimeSpan;

class Cache {
  private $values= [];

  public function __construct(private TimeSpan $expire) { }

  /**
   * Register a variable with a given name and fetch function
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