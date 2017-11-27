<?php

namespace Finesse\MiniDB\Tests;

use Finesse\MicroDB\Connection;
use Finesse\MiniDB\Database;
use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\MiniDB\Exceptions\InvalidReturnValueException;
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
     * Tests the methods creating builders
     */
    public function testCreateQuery()
    {
        $database = Database::create(['driver' => 'sqlite', 'dsn' => 'sqlite::memory:', 'prefix' => 'test_']);

        $query = $database->builder();
        $this->assertNull($query->table);

        $query = $database->table('items', 'i');
        $this->assertEquals('items', $query->table);
        $this->assertEquals('i', $query->tableAlias);

        $this->assertException(InvalidArgumentException::class, function () use ($database) {
            $database->table(['foo', 'bar']);
        }, function (InvalidArgumentException $exception) {
            $this->assertInstanceOf(QueryScribeInvalidArgumentException::class, $exception->getPrevious());
        });

        $this->assertException(InvalidReturnValueException::class, function () use ($database) {
            $database->table(function () {
                return 'foo';
            });
        }, function (InvalidReturnValueException $exception) {
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
}
