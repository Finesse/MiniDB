<?php

namespace Finesse\MiniDB\Tests;

use Finesse\MiniDB\Database;
use Finesse\MiniDB\Exceptions\IncorrectQueryException;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\MiniDB\Query;
use Finesse\MiniDB\QueryProxy;

/**
 * Tests the QueryProxy class
 *
 * @author Surgie
 */
class QueryProxyTest extends TestCase
{
    /**
     * Tests the `get` method
     */
    public function testGet()
    {
        $database = Database::create(['driver' => 'sqlite', 'dsn' => 'sqlite::memory:', 'prefix' => 'pre_']);
        $database->statement('CREATE TABLE pre_items(id INTEGER PRIMARY KEY ASC, name TEXT, value NUMERIC)');
        $database->insert(
            'INSERT INTO pre_items (name, value) VALUES (?, ?), (?, ?), (?, ?), (?, ?)',
            ['Banana', 123, 'Apple', -10, 'Pen', null, 'Bottle', 0]
        );

        // Default processor
        $superQuery = new QueryProxy($database->table('items'));
        $superQuery->whereNotNull('value')->orderBy('id', 'desc');
        $this->assertEquals([
            ['id' => 4, 'name' => 'Bottle', 'value' => 0],
            ['id' => 2, 'name' => 'Apple', 'value' => -10],
            ['id' => 1, 'name' => 'Banana', 'value' => 123]
        ], $superQuery->get());

        // Custom processor
        $superQuery = new class ($database->table('items')) extends QueryProxy {
            protected function processFetchedRow(array $row)
            {
                return implode('|', $row);
            }
        };
        $superQuery->whereNotNull('value')->orderBy('id', 'desc');
        $this->assertEquals(['4|Bottle|0', '2|Apple|-10', '1|Banana|123'], $superQuery->get());

        // Error handling
        $query = new Query($database);
        $superQuery = new QueryProxy($query);
        $this->assertException(IncorrectQueryException::class, function () use ($superQuery) {
            $superQuery->get();
        });
    }

    /**
     * Tests the `first` method
     */
    public function testFirst()
    {
        $database = Database::create(['driver' => 'sqlite', 'dsn' => 'sqlite::memory:', 'prefix' => 'pre_']);
        $database->statement('CREATE TABLE pre_items(id INTEGER PRIMARY KEY ASC, name TEXT, value NUMERIC)');
        $database->insert(
            'INSERT INTO pre_items (name, value) VALUES (?, ?), (?, ?), (?, ?), (?, ?)',
            ['Banana', 123, 'Apple', -10, 'Pen', null, 'Bottle', 0]
        );

        // Default processor
        $superQuery = new QueryProxy($database->table('items'));
        $superQuery->whereNotNull('value')->orderBy('id', 'desc');
        $this->assertEquals(['id' => 4, 'name' => 'Bottle', 'value' => 0], $superQuery->first());

        // Custom processor
        $superQuery = new class ($database->table('items')) extends QueryProxy {
            protected function processFetchedRow(array $row)
            {
                return implode('|', $row);
            }
        };
        $superQuery->whereNotNull('value')->orderBy('id', 'desc');
        $this->assertEquals('4|Bottle|0', $superQuery->first());

        // Null result
        $this->assertNull($superQuery->where('name', 'Orange')->first());

        // Error handling
        $query = new Query($database);
        $superQuery = new QueryProxy($query);
        $this->assertException(IncorrectQueryException::class, function () use ($superQuery) {
            $superQuery->first();
        });
    }

    /**
     * Tests the `chunk` method
     */
    public function testChunk()
    {
        $database = Database::create(['driver' => 'sqlite', 'dsn' => 'sqlite::memory:', 'prefix' => 'pre_']);
        $database->statement('CREATE TABLE pre_items(id INTEGER PRIMARY KEY ASC, name TEXT, value NUMERIC)');
        $database->insert(
            'INSERT INTO pre_items (name, value) VALUES (?, ?), (?, ?), (?, ?), (?, ?)',
            ['Banana', 123, 'Apple', -10, 'Pen', null, 'Bottle', 0]
        );

        // Default processor
        $superQuery = new QueryProxy($database->table('items'));
        $superQuery->orderBy('id', 'desc')->chunk(10, function ($rows) {
            $this->assertEquals([
                ['id' => 4, 'name' => 'Bottle', 'value' => 0],
                ['id' => 3, 'name' => 'Pen', 'value' => null],
                ['id' => 2, 'name' => 'Apple', 'value' => -10],
                ['id' => 1, 'name' => 'Banana', 'value' => 123]
            ], $rows);
        });

        // Custom processor
        $superQuery = new class ($database->table('items')) extends QueryProxy {
            protected function processFetchedRow(array $row)
            {
                return implode('|', $row);
            }
        };
        $superQuery->orderBy('id', 'desc')->chunk(10, function ($rows) {
            $this->assertEquals(['4|Bottle|0', '3|Pen|', '2|Apple|-10', '1|Banana|123'], $rows);
        });

        // Error handling
        $this->assertException(InvalidArgumentException::class, function () use ($database) {
            (new QueryProxy($database->table('items')))->chunk(-10, function () {});
        });
    }
}
