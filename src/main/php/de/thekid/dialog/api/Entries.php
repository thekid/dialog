<?php namespace de\thekid\dialog\api;

use de\thekid\dialog\{Repository, Storage};
use io\File;
use util\Date;
use web\rest\{Async, Delete, Entity, Put, Resource, Request, Response, Value};

#[Resource('/api/entries')]
class Entries {

  public function __construct(private Repository $repository, private Storage $storage) { }

  #[Put('/{id:.+(/.+)?}')]
  public function create(#[Value] $user, string $id, #[Entity] array<string, mixed> $attributes) {

    // Join places, autocomplete seems to only work for strings
    $suggest= '';
    foreach ($attributes['locations'] as $location) {
      $suggest.= ' '.$location['name'];
    }

    $result= $this->repository->replace($id, [
      'parent'      => $attributes['parent'] ?? null,
      'date'        => new Date($attributes['date']),
      'title'       => $attributes['title'],
      'keywords'    => $attributes['keywords'],
      'locations'   => $attributes['locations'],
      'content'     => $attributes['content'],
      'is'          => $attributes['is'],
      '_searchable' => [
        'boost'   => isset($attributes['is']['journey']) ? 2.0 : 1.0,
        'suggest' => trim($suggest),
        'content' => strip_tags(strtr($attributes['content'], ['<br>' => "\n", '</p><p>' => "\n"]))
      ],
    ]);

    // Ensure storage directory is created
    if ($result->created()) {
      $this->storage->folder($id)->create();
    }

    return $result->entry();
  }

  #[Put('/{id:.+(/.+)?}/images/{name}')]
  public function upload(#[Value] $user, string $id, string $name, #[Request] $req) {

    // Verify the (potentially unpublished) entry exists
    if (null === $this->repository->entry($id, published: false)) {
      return Response::error(400, 'Cannot upload to non-existant entry '.$id);
    }

    // Asynchronously process uploads
    return new Async(function() use($id, $name, $req) {
      if ($multipart= $req->multipart()) {
        $f= $this->storage->folder($id);
        foreach ($multipart->files() as $file) {
          yield from $file->transmit(new File($f, $file->name()));
        }

        // Fetch entry again, it might have changed in the meantime!
        $images= $this->repository->entry($id, published: false)['images'] ?? [];

        // Modify existing image, appending it if not existant
        $is= preg_match('/\.(mp4|mov|webm)$/i', $name) ? 'video' : 'image';
        $image= [
          'name'     => $name,
          'modified' => time(),
          'meta'     => (array)$req->param('meta') + ['dateTime' => gmdate('c')],
          'is'       => [$is => true]
        ];
        foreach ($images ?? [] as $i => $existing) {
          if ($name === $existing['name']) {
            $images[$i]= $image;
            goto set;
          }
        }
        $images[]= $image;

        // Sort by date and time, then write back
        set: usort($images, fn($a, $b) => $a['meta']['dateTime'] <=> $b['meta']['dateTime']);
        $this->repository->modify($id, ['$set' => ['images' => $images]]);
      }

      return Response::ok();
    });
  }

  #[Delete('/{id:.+(/.+)?}/images/{name}')]
  public function remove(#[Value] $user, string $id, string $name) {
    $this->repository->modify($id, ['$pull' => ['images' => ['name' => $name]]]);

    $deleted= [];
    $pattern= '/^(.+)-('.$name.')\.(webp|jpg|mp4)$/';
    foreach ($this->storage->folder($id)->entries() as $entry) {
      if (preg_match($pattern, $entry->name())) {
        $entry->asFile()->unlink();
        $deleted[]= $entry->name();
      }
    }
    return $deleted;
  }

  #[Put('/{id:.+(/.+)?}/published')]
  public function publish(#[Value] $user, string $id, #[Entity] Date $date) {

    // If this entry does not contain any images, use the first image of the latest
    // child element as the preview image. This will update the preview image every
    // time new content is added.
    $entry= $this->repository->entry($id, published: false);
    if (empty($entry['images'])) {
      $latest= $this->repository->children($id)->first();
      $preview= empty($latest['images']) ? null : ['slug' => $latest['slug'], ...$latest['images'][0]];
    } else {
      $preview= ['slug' => $id, ...$entry['images'][0]];
    }
    $this->repository->modify($id, ['$set' => ['published' => $date, 'preview' => $preview]]);

    return ['published' => $date];
  }
}