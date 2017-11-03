<?php

namespace Finesse\MiniDB\Tests;

use Finesse\MiniDB\Database;
use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\IncorrectQueryException;
use Finesse\QueryScribe\Exceptions\InvalidQueryException as QueryScribeInvalidQueryException;

/**
 * Tests the Query class
 *
 * @author Surgie
 */
class QueryTest extends TestCase
{
    /**
     * Tests the methods which retrieve something from database
     */
    public function testSelect()
    {
        $database = Database::create(['dns' => 'sqlite::memory:', 'prefix' => 'pre_']);
        $database->statement('CREATE TABLE pre_items(id INTEGER PRIMARY KEY ASC, name TEXT, value NUMERIC)');
        $database->insert(
            'INSERT INTO pre_items (name, value) VALUES (?, ?), (?, ?), (?, ?), (?, ?)',
            ['Banana', 123.4, 'Apple', -10, 'Pen', null, 'Bottle', 0]
        );

        $this->assertEquals([
            ['id' => 1, 'name' => 'Banana', 'value' => 123.4],
            ['id' => 2, 'name' => 'Apple', 'value' => -10],
            ['id' => 3, 'name' => 'Pen', 'value' => null],
            ['id' => 4, 'name' => 'Bottle', 'value' => 0]
        ], $database->table('items')->orderBy('id')->get());

        $this->assertEquals([
            ['name' => 'Bottle', 'value' => 0],
            ['name' => 'Banana', 'value' => 123.4]
        ], $database
            ->table('items')
            ->addSelect(['name', 'value'])
            ->where('value', '>=', 0)
            ->orderBy('id', 'desc')
            ->get());

        $this->assertEquals(
            ['id' => 3, 'name' => 'Pen', 'value' => null],
            $database->table('items')->whereNull('value')->first()
        );

        $this->assertNull($database->table('items')->where('value', '<', -1000)->first());
    }

    /**
     * Tests more error cases
     */
    public function testErrors()
    {
        $database = Database::create(['dns' => 'sqlite::memory:', 'prefix' => 'pre_']);
        $query = $database->table('animals');
        $query->table = null;
        $this->assertException(IncorrectQueryException::class, function () use ($query) {
            $query->get();
        }, function (IncorrectQueryException $exception) {
            $this->assertInstanceOf(QueryScribeInvalidQueryException::class, $exception->getPrevious());
        });

        $this->assertException(DatabaseException::class, function () use ($database) {
            $database->table('animals')->get();
        });
    }
}
