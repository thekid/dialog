<?php namespace de\thekid\dialog\unittest;

use com\mongodb\{Document, MongoConnection};
use de\thekid\dialog\{App, Storage};
use io\Path;
use test\{Args, Assert, Before, Test};
use util\Date;
use web\Environment;
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

#[Args]
class ServeTest {
  private $conn, $routing;

  /** Creates a new instance using the given MongoDB connection DSN */
  public function __construct(string $dsn) {
    $this->conn= new MongoConnection($dsn);
  }

  /** Serves a request */
  private function serve(string $method, string $uri, array<string, string> $headers= [], string $body= ''): Response {
    $req= new Request(new TestInput($method, $uri, $headers, $body));
    $res= new Response(new TestOutput());
    foreach ($this->routing->handle($req, $res) ?? [] as $_) { }
    return $res;
  }

  #[Before]
  public function setup() {
    $this->routing= new App(new Environment('test', '.'))
      ->connecting($this->conn)
      ->serving(new Storage('.'))
      ->routing()
    ;
  }

  #[Test]
  public function serves_favicon() {
    $res= $this->serve('GET', '/favicon.ico');
    Assert::equals([200, 'image/x-icon'], [$res->status(), $res->headers()['Content-Type']]);
  }

  #[Test]
  public function serves_robots_txt() {
    $res= $this->serve('GET', '/robots.txt');
    Assert::equals([200, 'text/plain; charset=utf-8'], [$res->status(), $res->headers()['Content-Type']]);
  }

  #[Test]
  public function serves_homepage() {
    $res= $this->serve('GET', '/');
    Assert::equals([200, 'text/html; charset=utf-8'], [$res->status(), $res->headers()['Content-Type']]);
  }

  #[Test]
  public function type_ahead_api_publicly_accessible() {
    $res= $this->serve('GET', '/api/suggestions?q=');
    Assert::equals([200, 'application/json; charset=utf-8'], [$res->status(), $res->headers()['Content-Type']]);
  }

  #[Test]
  public function upload_api_needs_authentication() {
    $res= $this->serve('PUT', '/api/entries/test', ['Content-Type' => 'application/json; charset=utf-8'], '{
      "title"    : "Test",
      "date"     : "2022-12-18 20:17:35+0100",
      "keywords" : [],
      "locations": [],
      "content"  : "...",
      "is"       : {"content": true}
    }');
    Assert::equals([400, 'application/json; charset=utf-8'], [$res->status(), $res->headers()['Content-Type']]);
  }
}