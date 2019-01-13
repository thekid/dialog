<?php namespace de\thekid\dialog;

use io\Path;
use lang\XPClass;
use util\cmd\Console;
use util\{Random, Secret};
use web\frontend\{Frontend, Templates, ClassesIn};
use web\handler\FilesFrom;
use web\rest\{RestApi, ResourcesIn};
use web\{Application, Filters};

class App extends Application {
  private $storage;

  public function __construct($env) {
    parent::__construct($env);

    // First time initialization. Create database and admin user 
    $this->storage= new Storage(new Path($env->arguments()[0] ?? '.'));
    if (!$this->storage->exists()) {
      $this->storage->create();
      Console::writeLine('Welcome to Dialog. An empty database has been set up @ ', $this->storage->path());

      $password= rtrim(base64_encode(new Random()->bytes(8)), '=');
      $this->storage->newUser('admin', new Secret($password));
      Console::writeLine("Your admin password is \e[1m", $password, "\e[0m\n");
    }
  }

  protected function routes() {
    $files= new FilesFrom(new Path($this->environment->webroot(), 'src/main/webapp'));
    $templates= new TemplateEngine(new Path($this->environment->webroot(), 'src/main/handlebars'));

    $create= ($name) ==> XPClass::forName($name)->newInstance($this->storage);
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