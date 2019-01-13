<?php namespace de\thekid\dialog;

use com\handlebarsjs\{HandlebarsEngine, FilesIn};
use io\Path;
use web\frontend\Templates;

class TemplateEngine implements Templates {
  private $backing;

  public function __construct(Path $templates) {
    $this->backing= (new HandlebarsEngine())->withTemplates(new FilesIn($templates));
  }

  public function write($name, $context, $out) {
    $this->backing->write($this->backing->load($name), $context, $out);
  }
}