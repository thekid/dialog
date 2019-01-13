<?php namespace de\thekid\dialog;

use io\Path;
use web\Application;
use web\frontend\{Frontend, Templates, ClassesIn};
use web\handler\FilesFrom;

class App extends Application {

  protected function routes() {
    $files= new FilesFrom(new Path($this->environment->webroot(), 'src/main/webapp'));
    $templates= new TemplateEngine(new Path($this->environment->webroot(), 'src/main/handlebars'));
    $frontend= new Frontend(new ClassesIn('de.thekid.dialog.actions'), $templates);

    return [
      '/favicon.ico' => $files,
      '/static'      => $files,
      '/'            => $frontend,
    ];
  }
}