<?php namespace de\thekid\dialog\storage;

use io\Path;
use io\streams\LinesIn;
use rdbms\DBConnection;

class Statements implements Migration {

  public function __construct(
    private Storage $storage,
    private Path $source,
    private array<string, string> $variables= []
  ) { }

  public function perform(): iterable {
    $replace= [];
    foreach ($this->variables as $name => $value) {
      $replace['$'.$name]= $this->storage->connection()->prepare('%s', $value);
    }
    yield 'Running '.$this->source->relativeTo(getcwd()).' with '.sizeof($replace).' variable(s)';

    $statement= '';
    foreach (new LinesIn($this->source->asFile(), 'utf-8') as $line) {
      if (preg_match('/^\-\-\s*(.+)$/', $line, $matches)) {
        yield strtr($matches[1], $replace);
      } else if (preg_match('/^(.+);\s*(\-\-.+)?$/', $line, $matches)) {
        $this->storage->connection()->query(strtr($statement.$matches[1], $replace));
        $statement= '';
      } else {
        $statement.= $line."\n";
      }
    }
    trim($statement) && $this->storage->connection()->query(strtr($statement, $replace));
  }
}