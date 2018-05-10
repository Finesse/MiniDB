# MiniDB

[![Latest Stable Version](https://poser.pugx.org/finesse/mini-db/v/stable)](https://packagist.org/packages/finesse/mini-db)
[![Total Downloads](https://poser.pugx.org/finesse/mini-db/downloads)](https://packagist.org/packages/finesse/mini-db)
![PHP from Packagist](https://img.shields.io/packagist/php-v/finesse/mini-db.svg)
[![Build Status](https://travis-ci.org/Finesse/MiniDB.svg?branch=master)](https://travis-ci.org/Finesse/MiniDB)
[![Coverage Status](https://coveralls.io/repos/github/Finesse/MiniDB/badge.svg?branch=master)](https://coveralls.io/github/Finesse/MiniDB?branch=master)
[![Dependency Status](https://www.versioneye.com/php/finesse:mini-db/badge)](https://www.versioneye.com/php/finesse:mini-db)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/0bd36bc4-7474-408e-ac6d-70f8609ce370/mini.png)](https://insight.sensiolabs.com/projects/0bd36bc4-7474-408e-ac6d-70f8609ce370)

Lightweight database abstraction in which rows are simple arrays. It has both a query builder for convenient fluent 
syntax and an interface for performing pure SQL queries.

```php
$database = Database::create([
    'driver'   => 'mysql',
    'dsn'      => 'mysql:host=localhost;dbname=my_database',
    'username' => 'root',
    'password' => 'qwerty',
    'prefix'   => 'test_'
]);

$database->statement('
    CREATE TABLE '.$database->addTablePrefix('users').' (
        id INT(11) NOT NULL AUTO_INCREMENT,
        email VARCHAR(50) NOT NULL, 
        account INT(11) NOT NULL DEFAULT 0
    )
');

$database->table('users')->insert([
    ['name' => 'Jack', 'account' => 1200],
    ['name' => 'Bob', 'account' => 500],
    ['name' => 'Richard', 'account' => 800]
]);

$database->table('users')->where('account', '>', 600)->get(); // Jack and Richard
```

Key features:

* Light with a small number of light dependencies.
* Extensible. Examples will come soon.
* The [query builder](https://github.com/Finesse/QueryScribe) and the 
  [database connector](https://github.com/Finesse/MicroDB) can be used separately.
* Supports table prefixes.
* No static facades. Explicit delivery using dependency injection. 
* Exceptions on errors.

Supported DBMSs:

* MySQL
* SQLite
* Maybe any other, didn't test it

If you need a new database system support please implement it [there](https://github.com/Finesse/MicroDB) and 
[there](https://github.com/Finesse/QueryScribe) using pull requests.


## Installation

You need [Composer](https://getcomposer.org) to use this library. Run in a console:
                                                                  
```bash
composer require finesse/mini-db
```


## Reference

### Getting started

You need to make a `Database` instance once:

```php
use Finesse\MiniDB\Database;

$database = Database::create([
    'driver'   => 'mysql',                     // DBMS type: 'mysql', 'sqlite' or anything else for other (optional) 
    'dsn'      => 'mysql:host=host;dbname=db', // PDO data source name (DSN)
    'username' => 'root',                      // Database username (optional)
    'password' => 'qwerty',                    // Database password (optional)
    'options'  => [],                          // PDO options (optional)
    'prefix'   => ''                           // Tables prefix (optional)
]);
```

See more about the PDO options at the [PDO constructor reference](http://php.net/manual/en/pdo.construct.php).

Alternatively you can create all the dependencies manually:

```php
use Finesse\MicroDB\Connection;
use Finesse\MiniDB\Database;
use Finesse\QueryScribe\Grammars\MySQLGrammar;
use Finesse\QueryScribe\PostProcessors\TablePrefixer;

$connection = Connection::create('mysql:host=host;dbname=db', 'username', 'password');
$grammar = new MySQLGrammar();
$tablePrefixer = new TablePrefixer('demo_');

$database = new Database($connection, $grammar, $tablePrefixer);
```

### Raw SQL queries

```php
$database->insertGetId('INSERT INTO users (name, email) VALUES (?, ?), (?, ?)', ['Ann', 'ann@gmail.com', 'Bob', 'bob@rambler.com']); // 19 (the last inserted row id)

$database->select('SELECT * FROM users WHERE name = ? OR email = ?', ['Jack', 'jack@example.com']);
/*
    [
        ['id' => 4, 'name' => 'Jack', 'email' => 'demon@mail.com', 'account' => 1230],
        ['id' => 17, 'name' => 'Bill', 'email' => 'jack@example.com', 'account' => -100]
    ]
 */
 
$database->import('path/to/file.sql');
```

The cell values are returned as they are returned by the underlying database connection. They are not casted 
automatically because casting can cause a data loss.

Table prefix is not applied in raw queries. Use `$database->addTablePrefix()` to apply it.

```php
$database->select('SELECT * FROM '.$database->addTablePrefix('users').' ORDER BY id');
```

Be careful, the `statements` and the `import` methods don't throw an exception if the second or a next statement of the 
query has an error. This is [a PDO bug](https://stackoverflow.com/a/28867491/1118709).

You can find more information and examples of raw queries [there](https://github.com/Finesse/MicroDB#reference).

### Query builder

Basic examples are presented here. You can find more cool examples 
[there](https://github.com/Finesse/QueryScribe/blob/master/docs/building-queries.md).

Values given to the query builder are treated safely to prevent SQL injections so you don't need to escape them.

#### Select

Many rows:

```php
$database
    ->table('users')
    ->where('status', 'active')
    ->orderBy('name')
    ->offset(40)
    ->limit(10)
    ->get();
    
/*
    [
        ['id' => 17, 'name' => 'Bill', 'email' => 'jack@example.com', 'status' => 'active'],
        ['id' => 4, 'name' => 'Jack', 'email' => 'demon@mail.com', 'status' => 'active']
    ]
 */
```

One row:

```php
$database
    ->table('users')
    ->where('status', 'active')
    ->orderBy('name')
    ->first();
    
/*
    ['id' => 17, 'name' => 'Bill', 'email' => 'jack@example.com', 'status' => 'active'] or null
 */
```

##### Pagination

We suggest [Pagerfanta](https://github.com/whiteoctober/Pagerfanta) to easily make pagination.

First install Pagerfanta using [composer](https://getcomposer.org) by running in a console:

```bash
composer require pagerfanta/pagerfanta
```

Then make a query from which the rows should be taken:

```php
$query = $database
    ->table('posts')
    ->where('category', 'archive')
    ->orderBy('date', 'desc');
    // Don't call ->get() here
```

And use Pagerfanta:

```php
use Finesse\MiniDB\ThirdParty\PagerfantaAdapter;
use Pagerfanta\Pagerfanta;

$paginator = new Pagerfanta(new PagerfantaAdapter($query));
$paginator->setMaxPerPage(10); // The number of rows on a page
$paginator->setCurrentPage(3); // The current page number

$currentPageRows = $paginator->getCurrentPageResults(); // The rows for the current page
$pagesCount = $paginator->getNbPages();                 // Total pages count
$haveToPaginate = $paginator->haveToPaginate();         // Whether the number of results is higher than the max per page
```

You can find more reference and examples for Pagerfanta [there](https://github.com/whiteoctober/Pagerfanta#usage).

##### Chunking rows

If you need to process a large amount of rows you can use chunking. In this approach portions of rows are fetched from 
the database instead of fetching all the rows at once.

```php
$database
    ->table('users')
    ->orderBy('id')
    ->chunk(100, function ($users) {
        foreach ($users as $user) {
            // Process a row here
        }
    });
```

#### Aggregates

```php
$database
    ->table('products')
    ->where('price', '>', 1000)
    ->count(); // 31
```

Other aggregate methods: `avg(column)`, `sum(column)`, `min(column)` and `max(column)`.

#### Insert

Many rows:

```php
$database->table('debts')->insert([
    ['name' => 'Sparrow', 'amount' => 13000, 'message' => 'Sneaky guy'],
    ['name' => 'Barbos', 'amount' => 4999, 'message' => null],
    ['name' => 'Pillower', 'message' => 'Call tomorrow']
]); // 3 (number of inserted rows)
```

The string array keys are the columns names.

One row:

```php
$database->table('debts')->insertGetId([
    'name' => 'Bigbigger',
    'amount' => -3500,
    'message' => 'I owe him'
]); // 4 (id of the inserted row)
```

From a select query:

```php
$database->table('debts')->insertFromSelect(['name', 'amount', 'message'], function ($query) {
    $query
        ->from('users')
        ->addSelect(['name', $query->raw('- account'), 'description'])
        ->where('status', 'debtor');
}); // 6 (number of inserted rows)
```

#### Update

```php
$database
    ->table('posts')
    ->where('date', '<', '2017-01-01')
    ->update([
        'status' => 'obsolete',
        'category' => null
    ]); // 5 (number of updated rows)
```

The array keys are the columns names.

#### Delete

```php
$database
    ->table('messages')
    ->where('sender_id', 1456)
    ->orWhere('status', 'stink')
    ->delete(); // 5 (number of deleted rows)
```

#### Helpers

Escape LIKE special wildcard characters:

```php
$searchString = '%iamhacker%';

$query->where('name', 'like', $query->escapeLikeWildcards($searchString).'_'); // "name" LIKE \%iamhacker\%_
```

Wrap a table or a column name in quotes:

```php
$query->whereRaw('MIN('.$query->quoteIdentifier('data"base').'.'.$query->quoteIdentifier('ta"ble').') > 10');
// or
$query->whereRaw('MIN('.$query->quoteCompositeIdentifier('data"base.ta"ble').') > 10'); // MIN("data""base"."ta""ble") > 10
```

The above methods are also available in `Database` object.


## Versions compatibility

The project follows the [Semantic Versioning](http://semver.org).


## License

MIT. See [the LICENSE](LICENSE) file for details.
