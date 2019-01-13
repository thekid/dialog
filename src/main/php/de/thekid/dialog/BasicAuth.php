<?php namespace de\thekid\dialog;

use util\Secret;
use web\Filter;

class BasicAuth implements Filter {

  public function __construct(private Storage $storage) { }

  public function filter($req, $res, $invocation) {
    if (1 === sscanf($req->header('Authorization'), 'Basic %s', $token)) {
      [$username, $password]= explode(':', base64_decode($token));
      if ($user= $this->storage->authenticate($username, new Secret($password))) {
        return $invocation->proceed($req, $res);
      }
    }

    $res->answer(401);
    $res->header('WWW-Authenticate', 'Basic realm="Dialog"');
  }
}