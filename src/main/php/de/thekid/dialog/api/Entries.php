<?php namespace de\thekid\dialog\api;

use de\thekid\dialog\Repository;
use io\{Path, Folder, File};
use util\Date;
use web\rest\{Put, Resource, Request, Response};

#[Resource('/api')]
class Entries {

  public function __construct(private Repository $repository, private Path $storage) { }

  /** Returns folder for a given entry */
  private function folder(string $entry): Folder {
    return new Folder($this->storage, 'image', $entry);
  }

  #[Put('/entries/{id:.+(/.+)?}')]
  public function create(string $id, array<string, mixed> $attributes) {
    $result= $this->repository->replace($id, [
      'parent'    => $attributes['parent'] ?? null,
      'date'      => new Date($attributes['date']),
      'title'     => $attributes['title'],
      'locations' => $attributes['locations'],
      'content'   => $attributes['content'],
      'is'        => $attributes['is'],
    ]);

    if ($result->upserted()) {
      $this->folder($id)->create();
      return ['created' => $id];
    } else {
      return ['updated' => $id];
    }
  }

  #[Put('/entries/{id:.+(/.+)?}/images/{name}')]
  public function upload(string $id, string $name, #[Request] $req) {
    if ($multipart= $req->multipart()) {
      $f= $this->folder($id);
      if (!$f->exists()) {

        // TODO: This does not work, we seem to need to consume all
        // uploaded files. Maybe just transfer them to /dev/null then?!
        return Response::error(417, 'Expectation Failed');
      }

      if ('100-continue' === $req->header('Expect')) $res->hint(100, 'Continue');
      foreach ($multipart->files() as $file) {
        $file->transfer(new File($f, $file->name()));
        yield;
      }
    }
    return Response::ok();
  }

  #[Put('/entries/{id:.+(/.+)?}/published')]
  public function publish(string $id, Date $date) {
    $images= [];
    $f= $this->folder($id);
    foreach ($f->entries() as $entry) {
      if (preg_match('/^([a-z]+)-(.+)\.webp$/', $entry->name(), $m)) {
        $images[$m[2]]= true;
      }
    }

    $this->repository->modify($id, ['$set' => [
      'published' => $date,
      'images'    => array_keys($images),
    ]]);
    return ['published' => $id];
  }
}