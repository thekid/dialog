<?php namespace de\thekid\dialog;

use com\handlebarsjs\{HandlebarsEngine, FilesIn};
use io\Path;
use lang\FunctionType;
use util\TimeSpan;
use web\frontend\Templates;

class TemplateEngine implements Templates {
  private $backing;
  private $globals= [];

  public function __construct(Path $templates) {
    $this->backing= (new HandlebarsEngine())->withTemplates(new FilesIn($templates));
  }

  /**
   * Registers a global variable along with a function to fetch it.
   * Optionally accepts a timespan for how long to cache the fetched
   * result in-memory.
   *
   * @param  string $var
   * @param  function(): var $func
   * @param  util.TimeSpan $cache
   * @return self
   */
  public function global($var, $func, ?TimeSpan $cache= null) {
    $this->globals[$var]= [
      'value'  => null,
      'time'   => null,
      'expire' => $cache ? $cache->getSeconds() : 0,
      'fetch'  => FunctionType::forName('function(): var')->cast($func),
    ];
    return $this;
  }

  /**
   * Implements `Templates::write()`, injecting previously registered
   * globals into the context.
   * 
   * @param  string $name
   * @param  [:var] $context
   * @param  io.streams.OutputStream $out
   * @return void
   */
  public function write($name, $context, $out) {
    $t= time();
    foreach ($this->globals as $var => &$global) {
      if ($t > $global['time'] || null === $global['time']) {
        $context[$var]= $global['value']= $global['fetch']();
        $global['time']= $t + $global['expire'];
      } else {
        $context[$var]= $global['value'];
      }
    }
    $this->backing->write($this->backing->load($name), $context, $out);
  }
}