<?php namespace de\thekid\dialog\import;

use io\File;
use io\streams\{InputStream, TextReader};
use lang\FormatException;
use net\daringfireball\markdown\Markdown;
use org\yaml\{YamlParser, StringInput};

/** @test de.thekid.dialog.unittest.DescriptionsTest */
class Descriptions {
  private $yaml= new YamlParser();
  private $markdown= new Markdown();

  public function parse(File|InputStream $source): Description {
    $reader= new TextReader($source, 'utf-8');
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
    } catch ($e) {
      throw new FormatException('Cannot parse '.$source->toString(), $e);
    } finally {
      $reader->close();
    }
  }
}
