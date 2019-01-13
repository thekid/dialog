<?php namespace de\thekid\dialog;

use io\Path;
use rdbms\DriverManager;
use text\hash\{Hashing, HashCode};
use util\{Secret, Date};

class Storage {
  private $index, $hashing;

  public function __construct(private Path $path) {
    $this->index= DriverManager::getConnection('sqlite://'.$this->path.'/dialog.db');
    $this->hashing= Hashing::sha256();
  }

  /** Returns this storage's base path */
  public function path(): Path ==> $this->path;

  /** Returns whether the storage exists */
  public function exists(): bool ==> new Path($this->path, 'dialog.db')->exists();

  /** Creates the storage; creating the database */
  public function create() {
    $this->index->query('drop table if exists user');
    $this->index->query('create table user (
      name text primary key not null,
      password text not null
    )');

    $this->index->query('drop table if exists album');
    $this->index->query('create table album (
      name text primary key not null,
      title text not null,
      created datetime not null
    )');
  }

  public function newUser(string $user, Secret $password): void {
    $this->index->insert('into user values (%s, %s)', $user, $this->hashing->new()->digest($password->reveal())->hex());
  }

  public function authenticate(string $user, Secret $password): ?bool {
    $q= $this->index->query('select * from user where name = %s', $user);
    if ($user= $q->next()) {
      return HashCode::fromHex($user['password'])->equals($this->hashing->new()->digest($password->reveal()));
    }
    return null;
  }

  public function allAlbums(): iterable {
    return $this->index->query('select * from album');
  }

  public function newestAlbums(): iterable {
    return $this->index->query('select * from album order by created desc');
  }

  public function findAlbum(string $name): array {
    return $this->index->query('select * from album where name = %s', $name)->next() ?: null;
  }

  public function createAlbum(string $name, string $title, Date $date): void {
    $this->index->insert(
      'into album (name, title, created) values (%s, %s, %s)',
      $name,
      $title,
      $date
    );
  }

  public function removeAlbum(string $name): void {
    $this->index->delete('from album where name = %s', $name);
  }
}