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

  public function global($var, $func) {
    $this->globals[$var]= FunctionType::forName('function(): var')->cast($func);
    return $this;
  }

  public function write($name, $context, $out) {
    foreach ($this->globals as $var => $func) {
      $context[$var]= $func();
    }
    $this->backing->write($this->backing->load($name), $context, $out);
  }
}