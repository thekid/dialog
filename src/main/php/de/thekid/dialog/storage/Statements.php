<?php namespace de\thekid\dialog\storage;

use io\Path;
use io\streams\LinesIn;
use rdbms\DBConnection;

class Statements implements Migration {

  public function __construct(private Path $source, private array<string, string> $variables= []) { }

  public function perform(DBConnection $conn): iterable {
    $replace= [];
    foreach ($this->variables as $name => $value) {
      $replace['$'.$name]= $conn->prepare('%s', $value);
    }
    yield 'Running '.$this->source->relativeTo(getcwd()).' with '.sizeof($replace).' variable(s)';

    $statement= '';
    foreach (new LinesIn($this->source->asFile(), 'utf-8') as $line) {
      if (preg_match('/^\-\-\s*(.+)$/', $line, $matches)) {
        yield strtr($matches[1], $replace);
      } else if (preg_match('/^(.+);\s*(\-\-.+)?$/', $line, $matches)) {
        $conn->query(strtr($statement.$matches[1], $replace));
        $statement= '';
      } else {
        $statement.= $line."\n";
      }
    }
    trim($statement) && $conn->query(strtr($statement, $replace));
  }
}