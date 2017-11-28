<?php

namespace Finesse\MiniDB\Tests\Parts;

use Finesse\MicroDB\Connection;
use Finesse\MicroDB\Exceptions\FileException as ConnectionFileException;
use Finesse\MicroDB\Exceptions\InvalidArgumentException as ConnectionInvalidArgumentException;
use Finesse\MicroDB\Exceptions\PDOException as ConnectionPDOException;
use Finesse\MiniDB\Database;
use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\FileException;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\MiniDB\Tests\TestCase;
use Finesse\QueryScribe\Grammars\SQLiteGrammar;

/**
 * Tests the RawStatementsTrait trait
 *
 * @author Surgie
 */
class RawStatementsTraitTest extends TestCase
{
    /**
     * Tests the plain database query methods
     */
    public function testRawQueries()
    {
        $connection = Connection::create('sqlite::memory:');
        $database = new Database($connection, new SQLiteGrammar());

        // Statement
        $database->statement('CREATE TABLE test(id INTEGER PRIMARY KEY ASC, name TEXT, value NUMERIC)');
        $this->assertNotEmpty($connection->select(
            'SELECT * FROM sqlite_master WHERE type = ? AND name = ?',
            ['table', 'test']
        ));

        // Statements
        $database->statements("
            INSERT INTO test (name, value) VALUES ('Banana', 123.4);
            INSERT INTO test (name, value) VALUES ('Apple', -10);
            INSERT INTO test (name, value) VALUES ('Pen', 0);
        ");
        $this->assertEquals(3, $connection->selectFirst('SELECT COUNT(*) AS count FROM test')['count']);

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

        // Import
        $stream = tmpfile();
        fputs($stream, "
            CREATE TABLE test2(id INTEGER PRIMARY KEY ASC, text TEXT);
            INSERT INTO test2 (text) VALUES ('Hello'), ('World');
            CREATE TABLE test3(id INTEGER PRIMARY KEY ASC, price NUMERIC);
            INSERT INTO test3 (price) VALUES (443000);
            INSERT INTO test3 (price) VALUES (45000), (12000);
        ");
        rewind($stream);
        $database->import($stream);
        $this->assertEquals([
            ['name' => 'test'],
            ['name' => 'test2'],
            ['name' => 'test3']
        ], $connection->select('SELECT name FROM sqlite_master WHERE type = ? ORDER BY name', ['table']));
        $this->assertEquals(2, $connection->selectFirst('SELECT COUNT(*) AS count FROM test2')['count']);
        $this->assertEquals(3, $connection->selectFirst('SELECT COUNT(*) AS count FROM test3')['count']);
    }

    /**
     * Tests error cases
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
            $database->selectFirst('SELECT * FROM sqlite_master WHERE name IN (?)', [['Anny', 'Bob']]);
        }, function (InvalidArgumentException $exception) {
            $this->assertInstanceOf(ConnectionInvalidArgumentException::class, $exception->getPrevious());
        });

        // Wrapping Connection FileException
        $this->assertException(FileException::class, function () use ($database) {
            $database->import('/not/existing/file/__gBpDtW');
        }, function (FileException $exception) {
            $this->assertInstanceOf(ConnectionFileException::class, $exception->getPrevious());
        });

        // Wrapping exceptions in all the other method
        $this->assertException(DatabaseException::class, function () use ($database) {
            $database->insertGetId('WRONG SQL');
        });
        $this->assertException(DatabaseException::class, function () use ($database) {
            $database->update('WRONG SQL');
        });
        $this->assertException(DatabaseException::class, function () use ($database) {
            $database->delete('WRONG SQL');
        });
        $this->assertException(DatabaseException::class, function () use ($database) {
            $database->statement('WRONG SQL');
        });
        $this->assertException(DatabaseException::class, function () use ($database) {
            $database->statements('WRONG SQL');
        });

        // Wrapping unexpected exception type
        $connection = new class extends Connection {
            public function __construct() {}
            public function insert(string $query, array $values = []): int
            {
                throw new \Exception('test');
            }
        };
        $database = new Database($connection, new SQLiteGrammar());
        $this->assertException(\Exception::class, function () use ($database) {
            $database->insert('');
        }, function (\Exception $exception) {
            $this->assertEquals('test', $exception->getMessage());
        });
    }
}
