<?php namespace de\thekid\dialog;

use com\handlebarsjs\{HandlebarsEngine, FilesIn};
use io\Path;
use web\frontend\Templates;

class TemplateEngine implements Templates {
  private $backing;
  private $globals= [];

  public function __construct(Path $templates) {
    $this->backing= (new HandlebarsEngine())->withTemplates(new FilesIn($templates));
  }

  /**
   * Registers a global variable along with either a constant value or
   * a function to fetch it.
   *
   * @param  string $var
   * @param  var $arg
   * @return self
   */
  public function global($var, $arg) {
    if ($arg instanceof \Closure) {
      $this->globals[$var]= $arg;
    } else {
      $this->globals[$var]= () ==> $arg;
    }
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
    foreach ($this->globals as $var => $func) {
      $context[$var]= $func($var);
    }
    $this->backing->write($this->backing->load($name), $context, $out);
  }
}