<?php

namespace Finesse\MiniDB\Tests;

use Finesse\MiniDB\Database;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\MiniDB\Query;
use Finesse\QueryScribe\Exceptions\InvalidArgumentException as QueryScribeInvalidArgumentException;

/**
 * Tests the SelectTrait trait
 *
 * @author Surgie
 */
class SelectTraitTest extends TestCase
{
    /**
     * Tests the methods which retrieve rows from database
     */
    public function testSelect()
    {
        $database = Database::create(['driver' => 'sqlite', 'dns' => 'sqlite::memory:', 'prefix' => 'pre_']);
        $database->statement('CREATE TABLE pre_items(id INTEGER PRIMARY KEY ASC, name TEXT, value NUMERIC)');
        $database->insert(
            'INSERT INTO pre_items (name, value) VALUES (?, ?), (?, ?), (?, ?), (?, ?)',
            ['Banana', 123.4, 'Apple', -10, 'Pen', null, 'Bottle', 0]
        );

        // Simple
        $this->assertEquals([
            ['id' => 1, 'name' => 'Banana', 'value' => 123.4],
            ['id' => 2, 'name' => 'Apple', 'value' => -10],
            ['id' => 3, 'name' => 'Pen', 'value' => null],
            ['id' => 4, 'name' => 'Bottle', 'value' => 0]
        ], $database->table('items')->orderBy('id')->get());

        // With where clause
        $this->assertEquals([
            ['name' => 'Bottle', 'value' => 0],
            ['name' => 'Banana', 'value' => 123.4]
        ], $database
            ->table('items')
            ->addSelect(['name', 'value'])
            ->where('value', '>=', 0)
            ->orderBy('id', 'desc')
            ->get());

        // Complicated
        $this->assertEquals([
            ['test' => 'Banana.', 'count' => 2],
            ['test' => 'Pen.', 'count' => 0]
        ], $database
            ->table('items')
            ->addSelect($database->raw($database->addTablePrefixToColumn('items.name').' || ?', ['.']), 'test')
            ->addSelect(function ($query) {
                $this->assertInstanceOf(Query::class, $query);
                $query->from('items', 'i2')->addCount()->whereColumn('items.value', '>', $query->raw('i2.value'));
            }, 'count')
            ->where(function ($query) {
                $this->assertInstanceOf(Query::class, $query);
                $query->where('items.value', '>', 0)->orWhere('name', 'Pen');
            })
            ->orderBy('id')
            ->get());

        // One row
        $this->assertEquals(
            ['id' => 3, 'name' => 'Pen', 'value' => null],
            $database->table('items')->whereNull('value')->first()
        );

        // Zero rows
        $this->assertNull($database->table('items')->where('value', '<', -1000)->first());
    }

    /**
     * Tests the aggregate functions
     */
    public function testAggregates()
    {
        $database = Database::create(['driver' => 'sqlite', 'dns' => 'sqlite::memory:', 'prefix' => 'pre_']);
        $database->statement('CREATE TABLE pre_items(id INTEGER PRIMARY KEY ASC, name TEXT, value NUMERIC)');
        $database->insert(
            'INSERT INTO pre_items (name, value) VALUES (?, ?), (?, ?), (?, ?), (?, ?)',
            ['Banana', 123.4, 'Apple', -10, 'Pen', null, 'Bottle', 0]
        );

        // Ordinary
        $this->assertEquals(3, $database->table('items')->whereNotNull('value')->count());
        $this->assertEquals(37.8, $database->table('items')->avg('value'), 0.001);
        $this->assertEquals(113.4, $database->table('items')->sum('value'), 0.001);
        $this->assertEquals(0, $database->table('items')->where('name', '!=', 'Apple')->min('value'));
        $this->assertEquals(123.4, $database->table('items')->where('name', '!=', 'Bottle')->max('value'));

        // No rows
        $this->assertEquals(0, $database->table('items')->where('value', '>', 1000)->count());
        $this->assertNull($database->table('items')->whereNull('value')->avg('value'));
        $this->assertNull($database->table('items')->where('value', '>', 1000)->sum('value'));
        $this->assertNull($database->table('items')->whereNull('value')->min('value'));
        $this->assertNull($database->table('items')->where('name', 'Car')->max('value'));

        $this->assertException(InvalidArgumentException::class, function () use ($database) {
            $database->table('items')->count(['foo', 'bar']);
        }, function (InvalidArgumentException $exception) {
            $this->assertInstanceOf(QueryScribeInvalidArgumentException::class, $exception->getPrevious());
        });
    }

    /**
     * Tests the `chunk` method
     */
    public function testChunk()
    {
        $database = Database::create(['driver' => 'sqlite', 'dns' => 'sqlite::memory:', 'prefix' => 'pre_']);
        $database->statement('CREATE TABLE pre_items(id INTEGER PRIMARY KEY ASC, name TEXT, value NUMERIC)');
        for ($i = 0; $i < 113; ++$i) {
            $database->insert('INSERT INTO pre_items (name, value) VALUES (?, ?)', ['Item '.$i, ($i % 10) * 10]);
        }

        // Ordinary
        $callsCount = 0;
        $database->table('items')->orderBy('id')->chunk(10, function ($rows) use (&$callsCount) {
            ++$callsCount;
            $this->assertInternalType('array', $rows);
            $this->assertCount($callsCount === 12 ? 3 : 10, $rows);
        });
        $this->assertEquals(12, $callsCount);

        // No rows
        $callsCount = 0;
        $database
            ->table('items')
            ->where('name', 'Super')
            ->orderBy('id')
            ->chunk(10, function () use (&$callsCount) {
                ++$callsCount;
            });
        $this->assertEquals(0, $callsCount);

        $this->assertException(InvalidArgumentException::class, function () use ($database) {
            $database->table('items')->orderBy('id')->chunk(-10, function () {});
        });
    }
}
