<?php namespace de\thekid\dialog\api;

use de\thekid\dialog\Repository;
use io\{Path, Folder, File};
use util\Date;
use web\rest\{Async, Delete, Put, Resource, Request, Response};

#[Resource('/api')]
class Entries {

  public function __construct(private Repository $repository, private Path $storage) { }

  /** Returns folder for a given entry */
  private function folder(string $entry): Folder {
    return new Folder($this->storage, 'image', $entry);
  }

  /** Returns media in a given entry */
  private function media(string $entry): array<mixed> {
    $media= [];
    $f= $this->folder($entry);
    foreach ($f->entries() as $entry) {
      if (preg_match('/^full-(.+)\.webp$/', $entry->name(), $m)) {
        $media[]= ['name' => $m[1], 'modified' => $entry->asFile()->lastModified(), 'is' => ['image' => true]];
      } else if (preg_match('/^video-(.+)\.mp4$/', $entry->name(), $m)) {
        $media[]= ['name' => $m[1], 'modified' => $entry->asFile()->lastModified(), 'is' => ['video' => true]];
      }
    }
    usort($media, fn($a, $b) => $a['name'] <=> $b['name']);
    return $media;
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

    // Ensure storage directory is created
    if ($result->created()) {
      $this->folder($id)->create();
    }

    return $result->entry();
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

  #[Delete('/entries/{id:.+(/.+)?}/images/{name}')]
  public function remove(string $id, string $name) {
    $pattern= '/^(.+)-('.$name.')\.(webp|jpg|mp4)$/';

    $f= $this->folder($id);
    $deleted= [];
    foreach ($f->entries() as $entry) {
      if (preg_match($pattern, $entry->name())) {
        $entry->asFile()->unlink();
        $deleted[]= $entry->name();
      }
    }
    return $deleted;
  }

  #[Put('/entries/{id:.+(/.+)?}/published')]
  public function publish(string $id, Date $date) {
    $this->repository->modify($id, ['$set' => [
      'published' => $date,
      'images'    => $this->media($id),
    ]]);
    return ['published' => $id];
  }
}