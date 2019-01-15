<?php namespace de\thekid\dialog;

use de\thekid\dialog\storage\Storage;
use io\Path;
use lang\XPClass;
use util\TimeSpan;
use util\cmd\Console;
use web\frontend\{Frontend, Templates, ClassesIn};
use web\handler\FilesFrom;
use web\rest\{RestApi, ResourcesIn};
use web\{Application, Filters};

class App extends Application {
  private $storage;

  public function __construct($env) {
    parent::__construct($env);

    // TODO: Add this to the xp-forge/web API, ensuring it's only executd
    // once even with `-m develop`.
    $this->initialize();
  }

  public function initialize() {
    $this->storage= new Storage(new Path($this->environment->arguments()[0] ?? '.'));
    Console::writeLine("\e[1m══ Welcome to Dialog ═══════════════════════════════════════════════════\e[0m");
    Console::writeLine('Storage @ ', $this->storage->path(), "\n");

    // Perform any necessary migrations
    $schemas= new Path($this->environment->webroot(), 'src/main/sql');
    foreach ($this->storage->migrations($schemas) as $migration) {
      foreach ($migration->perform() as $result) {
        Console::writeLine("\e[33;1m>\e[0m ", $result);
      }
    }

    Console::writeLine("> Initialization complete\n");
  }

  public function routes() {
    $files= new FilesFrom(new Path($this->environment->webroot(), 'src/main/webapp'));
    $cached= new Cache('dev' === $this->environment->profile() ? TimeSpan::seconds(10) : TimeSpan::hours(1));
    $cached->register('configuration', [$this->storage, 'configuration']);

    $templates= new TemplateEngine(new Path($this->environment->webroot(), 'src/main/handlebars'));
    $templates->global('configuration', $name ==> $cached->value($name));

    $create= $name ==> XPClass::forName($name)->newInstance($this->storage, $cached);
    $frontend= new Frontend(new ClassesIn('de.thekid.dialog.actions', $create), $templates);
    $api= new Filters(
      [new BasicAuth($this->storage)],
      new RestApi(new ResourcesIn('de.thekid.dialog.api', $create), '/api')
    );

    return [
      '/favicon.ico' => $files,
      '/static'      => $files,
      '/api'         => $api,
      '/'            => $frontend,
    ];
  }
}