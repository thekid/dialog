<?php namespace de\thekid\dialog;

use com\mongodb\MongoConnection;
use inject\{Bindings, Injector};
use io\Path;
use util\{TimeZone, Secret};
use web\Application;
use web\auth\Basic;
use web\filters\BehindProxy;
use web\frontend\helpers\{Assets, Dates, Numbers};
use web\frontend\{Frontend, AssetsFrom, AssetsManifest, HandlersIn, Handlebars};
use web\rest\{RestApi, ResourcesIn};

/** @test de.thekid.dialog.unittest.ServeTest */
class App extends Application {
  private $conn= null;
  private $storage= null;

  /** Passes MongoDB connection to use */
  public function connecting(MongoConnection $conn): self {
    $this->conn= $conn;
    return $this;
  }

  /** Passes storage to use */
  public function serving(Storage $storage): self {
    $this->storage= $storage;
    return $this;
  }

  /** Returns routing for this web application */
  public function routes() {
    $preferences= new Preferences($this->environment, 'config');
    $this->conn??= new MongoConnection($preferences->get('mongo', 'uri'));
    $this->storage??= new Storage($this->environment->arguments()[0]);
    $repository= new Repository($this->conn, $preferences->optional('mongo', 'db', 'dialog'));
    $inject= new Injector(Bindings::using()
      ->instance($repository)
      ->instance($this->storage)
      ->instance(new Signing(new Secret($this->conn->protocol()->dsn(password: true)))->tolerating(seconds: 30))
    );

    // Authenticate API users against MongoDB
    $auth= new Basic('API', $repository->authenticate(...));

    // If behind a proxy, use an environment variable to rewrite the request URI
    if ($service= $this->environment->variable('SERVICE')) {
      $this->install(new BehindProxy([$service => '/']));
    }

    // Cache static content for one week, immutable fingerprinted assets for one year
    $caching= ['Cache-Control' => 'max-age=604800'];
    $manifest= new AssetsManifest($this->environment->path('src/main/webapp/assets/manifest.json'));
    $static= new AssetsFrom($this->environment->path('src/main/webapp'))->with($caching);
    $assets= new AssetsFrom($this->environment->path('src/main/webapp'))->with(fn($file) => [
      'Cache-Control' => $manifest->immutable($file) ?? 'max-age=604800, must-revalidate'
    ]);

    return [
      '/static'      => $static,
      '/favicon.ico' => $static,
      '/robots.txt'  => $static,
      '/assets'      => $assets,
      '/image'       => $this->storage->with($caching),
      '/api'         => $auth->optional(new RestApi(new ResourcesIn('de.thekid.dialog.api', $inject->get(...)))),
      '/'            => new Frontend(
        new HandlersIn('de.thekid.dialog.web', $inject->get(...)),
        new Handlebars($this->environment->path('src/main/handlebars'), [
          new Dates(new TimeZone('Europe/Berlin')),
          new Numbers(),
          new Assets($manifest),
          $inject->get(Helpers::class),
          new Scripts($this->environment->path('src/main/js'), 'dev' === $this->environment->profile()),
        ])
      ),
    ];
  }
}