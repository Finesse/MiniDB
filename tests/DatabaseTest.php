<?php

namespace Finesse\MiniDB\Tests;

use Finesse\MicroDB\Connection;
use Finesse\MicroDB\Exceptions\InvalidArgumentException as ConnectionInvalidArgumentException;
use Finesse\MicroDB\Exceptions\PDOException as ConnectionPDOException;
use Finesse\MiniDB\Database;
use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\QueryScribe\Exceptions\InvalidArgumentException as QueryScribeInvalidArgumentException;
use Finesse\QueryScribe\Exceptions\InvalidReturnValueException as QueryScribeInvalidReturnValueException;
use Finesse\QueryScribe\Grammars\CommonGrammar;
use Finesse\QueryScribe\Grammars\MySQLGrammar;
use Finesse\QueryScribe\Grammars\SQLiteGrammar;

/**
 * Tests the Database class
 *
 * @author Surgie
 */
class DatabaseTest extends TestCase
{
    /**
     * Tests the `create` method
     */
    public function testCreate()
    {
        $database = Database::create([
            'dsn' => 'sqlite::memory:'
        ]);
        $this->assertInstanceOf(Connection::class, $database->getConnection());
        $this->assertInstanceOf(CommonGrammar::class, $database->getGrammar());
        $this->assertEquals('', $database->getTablePrefixer()->tablePrefix);

        $database = Database::create([
            'driver' => 'SQLite',
            'dsn' => 'sqlite::memory:',
            'username' => null,
            'password' => null,
            'options' => null,
            'prefix' => 'test_'
        ]);
        $this->assertInstanceOf(Connection::class, $database->getConnection());
        $this->assertInstanceOf(SQLiteGrammar::class, $database->getGrammar());
        $this->assertEquals('test_', $database->getTablePrefixer()->tablePrefix);

        $database = Database::create(['driver' => 'MySQL', 'dsn' => 'sqlite::memory:']);
        $this->assertInstanceOf(MySQLGrammar::class, $database->getGrammar());

        $this->assertException(DatabaseException::class, function () {
            Database::create([
                'dsn' => 'foo:bar'
            ]);
        });
    }

    /**
     * Tests the plain database query methods
     */
    public function testRawQueries()
    {
        $connection = Connection::create('sqlite::memory:');
        $connection->statement('CREATE TABLE test(id INTEGER PRIMARY KEY ASC, name TEXT, value NUMERIC)');
        $connection->insert(
            'INSERT INTO test (name, value) VALUES (?, ?), (?, ?), (?, ?)',
            ['Banana', 123.4, 'Apple', -10, 'Pen', 0]
        );
        $database = new Database($connection, new SQLiteGrammar());

        // Select
        $this->assertEquals([
            ['id' => 1, 'name' => 'Banana', 'value' => 123.4],
            ['id' => 3, 'name' => 'Pen', 'value' => 0]
        ], $database->select('SELECT * FROM test WHERE value >= ? ORDER BY id', [0]));

        // Select first
        $this->assertEquals(
            ['id' => 1, 'name' => 'Banana', 'value' => 123.4],
            $database->selectFirst('SELECT * FROM test ORDER BY id')
        );
        $this->assertNull($database->selectFirst('SELECT * FROM test WHERE name = ?', ['Orange']));

        // Insert and get the count
        $this->assertEquals(2, $database->insert(
            'INSERT INTO test (name, value) VALUES (?, ?), (?, ?)',
            ['Orange', 314, 'Pillow', 219]
        ));
        $this->assertEquals(5, $connection->selectFirst('SELECT COUNT(*) AS count FROM test')['count']);

        // Insert and get the last id
        $this->assertEquals(6, $database->insertGetId('INSERT INTO test (name, value) VALUES (?, ?)', ['Mug', -1]));
        $this->assertEquals(6, $connection->selectFirst('SELECT COUNT(*) AS count FROM test')['count']);

        // Update
        $this->assertEquals(3, $database->update('UPDATE test SET name = name || ? WHERE value > ?', ['!', 100]));
        $this->assertEquals([
            ['name' =>'Banana!'],
            ['name' =>'Orange!'],
            ['name' =>'Pillow!']
        ], $connection->select('SELECT name FROM test WHERE value > ? ORDER BY id', [100]));

        // Delete
        $this->assertEquals(2, $database->delete('DELETE FROM test WHERE value < ?', [0]));
        $this->assertEquals([
            ['name' =>'Banana!'],
            ['name' =>'Pen'],
            ['name' =>'Orange!'],
            ['name' =>'Pillow!']
        ], $connection->select('SELECT name FROM test ORDER BY id'));

        // Statement
        $database->statement('DROP TABLE test');
        $this->assertEmpty($connection->select(
            'SELECT name FROM sqlite_master WHERE type = ? AND name = ?',
            ['table', 'test'])
        );
    }

    /**
     * Tests the methods creating builders
     */
    public function testCreateQuery()
    {
        $database = Database::create(['driver' => 'sqlite', 'dsn' => 'sqlite::memory:', 'prefix' => 'test_']);
        $query = $database->table('items', 'i');
        $this->assertEquals('items', $query->table);
        $this->assertEquals('i', $query->tableAlias);

        $this->assertException(InvalidArgumentException::class, function () use ($database) {
            $database->table(['foo', 'bar']);
        }, function (InvalidArgumentException $exception) {
            $this->assertInstanceOf(QueryScribeInvalidArgumentException::class, $exception->getPrevious());
        });

        $this->assertException(InvalidArgumentException::class, function () use ($database) {
            $database->table(function () {
                return 'foo';
            });
        }, function (InvalidArgumentException $exception) {
            $this->assertInstanceOf(QueryScribeInvalidReturnValueException::class, $exception->getPrevious());
        });
    }

    /**
     * Tests the `with...` methods
     */
    public function testCopying()
    {
        $database = Database::create(['driver' => 'sqlite', 'dsn' => 'sqlite::memory:', 'prefix' => 'foo-']);
        $database2 = $database->withTablePrefix('bar-');
        $this->assertEquals($database->getConnection(), $database2->getConnection());
        $this->assertEquals($database->getGrammar(), $database2->getGrammar());
        $this->assertEquals('bar-', $database2->getTablePrefixer()->tablePrefix);
        $this->assertEquals('foo-', $database->getTablePrefixer()->tablePrefix);

        $database2 = $database->withTablesPrefixed('bar-');
        $this->assertEquals($database->getConnection(), $database2->getConnection());
        $this->assertEquals($database->getGrammar(), $database2->getGrammar());
        $this->assertEquals('bar-foo-', $database2->getTablePrefixer()->tablePrefix);;
        $this->assertEquals('foo-', $database->getTablePrefixer()->tablePrefix);
    }

    /**
     * Tests the `addTablePrefix` and `addTablePrefixToColumn` methods
     */
    public function testAddTablePrefix()
    {
        $database = Database::create(['driver' => 'sqlite', 'dsn' => 'sqlite::memory:', 'prefix' => 'prefix_']);

        $this->assertEquals('prefix_tab1', $database->addTablePrefix('tab1'));
        $this->assertEquals('database.prefix_table', $database->addTablePrefix('database.table'));

        $this->assertEquals('column1', $database->addTablePrefixToColumn('column1'));
        $this->assertEquals('prefix_table.column1', $database->addTablePrefixToColumn('table.column1'));
        $this->assertEquals('database.prefix_table.column1', $database->addTablePrefixToColumn('database.table.column1'));
    }

    /**
     * Tests more error cases
     */
    public function testErrors()
    {
        $database = Database::create(['driver' => 'sqlite', 'dsn' => 'sqlite::memory:']);

        // Wrapping Connection PDOException
        $this->assertException(DatabaseException::class, function () use ($database) {
            $database->select('WRONG SQL', ['foo', 'bar', true, 123]);
        }, function (DatabaseException $exception) {
            $this->assertStringEndsWith(
                '; SQL query: (WRONG SQL); bound values: ["foo", "bar", true, 123]',
                $exception->getMessage()
            );
            $this->assertEquals('WRONG SQL', $exception->getQuery());
            $this->assertEquals(['foo', 'bar', true, 123], $exception->getValues());
            $this->assertInstanceOf(ConnectionPDOException::class, $exception->getPrevious());
        });

        // Wrapping Connection InvalidArgumentException
        $this->assertException(InvalidArgumentException::class, function () use ($database) {
            $database->select('SELECT * FROM sqlite_master WHERE name IN (?)', [['Anny', 'Bob']]);
        }, function (InvalidArgumentException $exception) {
            $this->assertInstanceOf(ConnectionInvalidArgumentException::class, $exception->getPrevious());
        });

        // Wrapping any other exception
        $connection = new class extends Connection {
            public function __construct() {}
            public function select(string $query, array $values = []): array
            {
                throw new \Exception('test');
            }
        };
        $database = new Database($connection, new SQLiteGrammar());
        $this->assertException(\Exception::class, function () use ($database) {
            $database->select('');
        }, function (\Exception $exception) {
            $this->assertEquals('test', $exception->getMessage());
        });
    }
}
