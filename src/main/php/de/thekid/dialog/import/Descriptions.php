<?php namespace de\thekid\dialog\import;

use io\File;
use io\streams\TextReader;
use lang\FormatException;
use net\daringfireball\markdown\Markdown;
use org\yaml\{YamlParser, StringInput};

class Descriptions {
  private $yaml= new YamlParser();
  private $markdown= new Markdown();

  public function parse(File $file): Description {
    $reader= new TextReader($file, 'utf-8');
    try {
      $line= $reader->readLine();
      if ('---' !== $line) throw new FormatException('Missing YAML front matter');

      $yaml= '';
      while ('---' !== ($line= $reader->readLine()) && null !== $line) {
        $yaml.= $line."\n";
      }
      
      return new Description(
        $this->yaml->parse(new StringInput($yaml)),
        $this->markdown->transform($reader)
      );
    } finally {
      $reader->close();
    }
  }
}
