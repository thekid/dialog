<?php namespace de\thekid\dialog\api;

use de\thekid\dialog\Repository;
use io\{Path, Folder, File};
use util\Date;
use web\rest\{Async, Put, Resource, Request, Response};

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

    // Verify the folder (and thus the entry) exists
    $f= $this->folder($id);
    if (!$f->exists()) {
      return Response::error(400, 'Cannot upload to non-existant entry '.$id);
    }

    // Asynchronously process uploads
    return new Async(function() use($f, $name, $req) {
      if ($multipart= $req->multipart()) {
        foreach ($multipart->files() as $file) {
          yield from $file->transmit(new File($f, $file->name()));
        }
      }

      return Response::ok();
    });
  }

  #[Put('/entries/{id:.+(/.+)?}/published')]
  public function publish(string $id, Date $date) {
    $images= [];
    $f= $this->folder($id);
    foreach ($f->entries() as $entry) {
      if (preg_match('/^full-(.+)\.webp$/', $entry->name(), $m)) {
        $images[]= ['name' => $m[1], 'is' => ['image' => true]];
      } else if (preg_match('/^video-(.+)\.mp4$/', $entry->name(), $m)) {
        $images[]= ['name' => $m[1], 'is' => ['video' => true]];
      }
    }
    ksort($images);

    $this->repository->modify($id, ['$set' => [
      'published' => $date,
      'images'    => $images,
    ]]);
    return ['published' => $id];
  }
}