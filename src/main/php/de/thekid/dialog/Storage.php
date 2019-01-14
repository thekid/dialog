<?php namespace de\thekid\dialog;

use io\Path;
use rdbms\DriverManager;
use text\hash\Hashing;
use util\{Secret, Date};

class Storage {
  const DATABASE= 'dialog.db';
  const DEFAULTS= ['title' => 'Dialog', 'theme' => 'default'];

  private $index, $hashing;

  public function __construct(private Path $path) {
    $database= new Path($this->path, self::DATABASE)->normalize();
    $this->index= DriverManager::getConnection('sqlite://./'.$database->toString('/'));
    $this->hashing= Hashing::sha256();
  }

  /** Returns this storage's base path */
  public function path(): Path ==> $this->path;

  /** Returns whether the storage exists */
  public function exists(): bool ==> new Path($this->path, self::DATABASE)->exists();

  /** Creates and initializes the storage; creating the database */
  public function create() {
    $this->index->query('drop table if exists configuration');
    $this->index->query('drop table if exists user');
    $this->index->query('drop table if exists album');
    $this->initialize();
  }

  /** Initializes the storage; creating database tables if necessary */
  public function initialize() {
    $this->index->query('create table if not exists configuration (
      name text primary key not null,
      value text not null
    )');
    $this->index->query('create table if not exists user (
      name text primary key not null,
      password text not null
    )');
    $this->index->query('create table if not exists album (
      name text primary key not null,
      title text not null,
      created datetime not null
    )');
  }

  public function configuration() {
    $configuration= self::DEFAULTS;
    foreach ($this->index->query('select name, value from configuration') as $c) {
      $configuration[$c['name']]= $c['value'];
    }
    return $configuration;
  }

  public function configure($configuration) {
    foreach ($configuration as $name => $value) {
      $this->index->query('replace into configuration (name, value) values (%s, %s)', $name, $value);
    }
  }

  public function newUser(string $user, Secret $password): void {
    $this->index->insert('into user values (%s, %s)', $user, $this->hashing->digest($password->reveal()));
  }

  public function authenticate(string $user, Secret $password): ?bool {
    $q= $this->index->query('select * from user where name = %s', $user);
    if ($user= $q->next()) {
      return $this->hashing->digest($password->reveal())->equals($user['password']);
    }
    return null;
  }

  public function allAlbums(): iterable {
    return $this->index->query('select * from album');
  }

  public function newestAlbums(): iterable {
    return $this->index->query('select * from album order by created desc');
  }

  public function findAlbum(string $name): ?array {
    return $this->index->query('select * from album where name = %s', $name)->next() ?: null;
  }

  public function createAlbum(string $name, string $title, Date $created): void {
    $this->index->insert(
      'into album (name, title, created) values (%s, %s, %s)',
      $name,
      $title,
      $created,
    );
  }

  public function updateAlbum(string $name, string $title, Date $created): void {
    $this->index->update(
      'album set title = %s, created = %s where name = %s',
      $title,
      $created,
      $name,
    );
  }

  public function removeAlbum(string $name): void {
    $this->index->delete('from album where name = %s', $name);
  }
}