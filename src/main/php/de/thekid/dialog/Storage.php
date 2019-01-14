<?php namespace de\thekid\dialog;

use io\{Path, File};
use rdbms\DriverManager;
use text\hash\Hashing;
use util\{Secret, Date, Random};

class Storage {
  const DATABASE= 'dialog';
  const VERSION= 1;

  private $path, $index, $hashing;

  public function __construct(Path $path) {
    $this->path= $path->asRealpath();

    $db= new Path($this->path, self::DATABASE.self::VERSION.'.db');
    $this->index= DriverManager::getConnection('sqlite://./'.urlencode($db));
    $this->hashing= Hashing::sha256();
  }

  /** Returns base path for this storage */
  public function path() ==> $this->path;

  /** Returns database connection */
  public function connection() ==> $this->index;

  /** Checks for migrations */
  public function migrations(Path $migrations): iterable {
    $current= new Path($this->path, self::DATABASE.self::VERSION.'.db');

    // If current database exists, nothing is to be done
    if ($current->exists()) return;

    // Check which migrations to apply
    $version= 0;
    foreach (glob($this->path.DIRECTORY_SEPARATOR.self::DATABASE.'*.db') as $database) {
      sscanf(basename($database), self::DATABASE.'%d', $version);
      $versions[$version]= $database;
    }

    // If no database file exists, shortcut to creating current version
    // directly; generating a random admin password. Otherwise, apply migrations
    if (empty($versions)) {
      $password= rtrim(base64_encode(new Random()->bytes(8)), '=');
      yield new Statements(new Path($migrations, sprintf('create-v%d.sql', self::VERSION)), [
        'PASS' => $password,
        'HASH' => $this->hashing->digest($password),
      ]);
    } else {
      krsort($versions, SORT_NUMERIC | SORT_DESC);
      $newest= key($versions);

      yield new CopyOf(new Path($versions[$newest]), $current);
      yield new Statements(new Path($migrations, sprintf('migrate-v%d-v%d.sql', $newest, self::VERSION)));
    }
  }

  public function configuration() {
    $configuration= [];
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

  public function users(): iterable {
    return $this->index->query('select * from user');
  }

  public function findUser(string $name): ?array {
    return $this->index->query('select * from user where name = %s', $name)->next();
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

  public function createAlbum(string $name, string $title, string $description, Date $created): void {
    $this->index->insert(
      'into album (name, title, description, created) values (%s, %s, %s, %s)',
      $name,
      $title,
      $description,
      $created,
    );
  }

  public function updateAlbum(string $name, string $title, string $description, Date $created): void {
    $this->index->update(
      'album set title = %s, description = %s, created = %s where name = %s',
      $title,
      $description,
      $created,
      $name,
    );
  }

  public function removeAlbum(string $name): void {
    $this->index->delete('from album where name = %s', $name);
  }
}