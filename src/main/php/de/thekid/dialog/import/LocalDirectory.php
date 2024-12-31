<?php namespace de\thekid\dialog\import;

use Generator;
use de\thekid\dialog\processing\{Files, Images, Videos, ResizeTo};
use io\{File, Folder};
use lang\{Throwable, IllegalArgumentException};
use util\cmd\{Command, Arg};
use util\log\Logging;
use webservices\rest\{Endpoint, RestUpload};

/**
 * Imports items from a local directory.
 *
 * The item type is determined by the presence of one of these files
 * in the given origin folder.
 *
 * - content.md: A simple content element
 * - journey.md: A journey element containt content elements
 * - cover.md: The image to use for the cover page
 */
class LocalDirectory extends Command {
  private static $implementations= [
    'content.md' => new Content(...),
    'journey.md' => new Journey(...),
    'cover.md'   => new Cover(...),
  ];
  private $source, $api;

  /** Sets origin folder, e.g. `./imports/album` */
  #[Arg(position: 0)]
  public function from(string $origin): void {
    foreach (self::$implementations as $source => $implementation) {
      $file= new File($origin, $source);
      if (!$file->exists()) continue;

      $this->source= $implementation(new Folder($origin), $file);
      return;
    }

    throw new IllegalArgumentException(sprintf(
      'Cannot locate any of [%s] in %s',
      implode(', ', array_keys(self::$implementations)),
      $origin
    ));
  }

  /** Sets API url, e.g. `http://user:pass@localhost:8080/api` */
  #[Arg(position: 1)]
  public function using(string $api): void {
    $this->api= new Endpoint($api);
  }

  /** Add verbose logging for API calls */
  #[Arg]
  public function useVerbose() {
    $this->api->setTrace(Logging::all()->toConsole());
  }

  /** Runs this command */
  public function run(): int {
    $files= new Files()
      ->matching(['.jpg', '.jpeg', '.png', '.webp'], new Images()
        ->targeting('preview', new ResizeTo(720, 'jpg'))
        ->targeting('thumb', new ResizeTo(1024, 'webp'))
        ->targeting('full', new ResizeTo(3840, 'webp'))
      )
      ->matching(['.mp4', '.mpeg', '.mov'], new Videos()
        ->targeting('preview', new ResizeTo(720, 'jpg'))
        ->targeting('thumb', new ResizeTo(1024, 'webp'))
      )
    ;

    $this->out->writeLine("[+] \e[37;1m{$this->source->toString()}\e[0m");
    for ($tasks= $this->source->synchronize($files); $task= $tasks->current(); ) {
      $this->out->write(' => ', $task->description(), ':');

      try {
        $result= $task->execute($this->api);
        if ($result instanceof Generator) {
          $this->out->write("\e[34m");
          foreach ($result as $input => $output) {
            $this->out->write(' <', $input, ' -> ', $output, '>');
          }
          $this->out->writeLine("\e[0m");
          $tasks->send($result->getReturn());
        } else {
          $this->out->writeLine(" \e[32m✓\e[0m");
          $tasks->send($result);
        }
      } catch ($e) {
        $this->out->writeLine(" \e[31m⨯\e[0m ", Throwable::wrap($e));
        return 1;
      }
    }

    $this->out->writeLine(" => Finished at \e[34m", date('r'), "\e[0m");
    return 0;
  }
}