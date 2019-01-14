<?php namespace de\thekid\dialog;

use com\handlebarsjs\{HandlebarsEngine, FilesIn};
use io\Path;
use lang\FunctionType;
use web\frontend\Templates;

class TemplateEngine implements Templates {
  private $backing;
  private $globals= [];

  public function __construct(Path $templates) {
    $this->backing= (new HandlebarsEngine())->withTemplates(new FilesIn($templates));
  }

  /**
   * Registers a global variable along with a function to fetch it.
   *
   * @param  string $var
   * @param  function(var): var $func
   * @return self
   */
  public function global($var, $func) {
    $this->globals[$var]= FunctionType::forName('function(var): var')->cast($func);
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