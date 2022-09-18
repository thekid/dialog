<?php namespace de\thekid\dialog;

use com\mongodb\MongoConnection;
use io\Path;
use text\hash\Hashing;
use util\{TimeZone, Secret};
use web\Application;
use web\auth\Basic;
use web\filters\BehindProxy;
use web\frontend\helpers\{Assets, Dates};
use web\frontend\{Frontend, AssetsFrom, AssetsManifest, HandlersIn, Handlebars};
use web\handler\FilesFrom;
use web\rest\{RestApi, ResourcesIn};

class App extends Application {

  /** Returns routing for this web application */
  public function routes() {
    $conn= new MongoConnection($this->environment->variable('MONGO_URI'));
    $database= $conn->database($this->environment->variable('MONGO_DB') ?? 'dialog');
    $storage= new Path($this->environment->arguments()[0]);
    $new= fn($class) => $class->newInstance($database, $storage);

    // Authenticate API users against MongoDB
    $auth= new Basic('API', function($user, Secret $secret) use($database) {
      $cursor= $database->collection('users')->find([
        'handle' => $user,
        'hash'   => Hashing::sha256()->digest($secret->reveal())->hex()
      ]);
      return $cursor->first();
    });

    // If behind a proxy, use an environment variable to rewrite the request URI
    if ($service= $this->environment->variable('SERVICE')) {
      $this->install(new BehindProxy([$service => '/']));
    }

    // Cache static content for one week, immutable fingerprinted assets for one year
    $manifest= new AssetsManifest($this->environment->path('src/main/webapp/assets/manifest.json'));
    $static= ['Cache-Control' => 'max-age=604800'];
    return [
      '/image'  => new FilesFrom($this->environment->arguments()[0])->with($static),
      '/static' => new FilesFrom($this->environment->path('src/main/webapp'))->with($static),
      '/assets' => new AssetsFrom($this->environment->path('src/main/webapp'))->with(fn($file) => [
        'Cache-Control' => $manifest->immutable($file) ?? 'max-age=31536000, must-revalidate'
      ]),
      '/api'    => $auth->required(new RestApi(new ResourcesIn('de.thekid.dialog.api', $new))),
      '/'       => new Frontend(
        new HandlersIn('de.thekid.dialog.web', $new),
        new Handlebars($this->environment->path('src/main/handlebars'), [
          new Dates(TimeZone::getByName('Europe/Berlin')),
          new Assets($manifest),
        ])
      ),
    ];
  }
}