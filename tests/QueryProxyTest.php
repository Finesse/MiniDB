<?php

namespace Finesse\MiniDB\Tests;

use Finesse\MiniDB\Database;
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
     * Tests the fetch rows processing
     */
    public function testProcessFetchedRow()
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
        $this->assertEquals(['id' => 4, 'name' => 'Bottle', 'value' => 0], $superQuery->first());

        // Custom processor
        $superQuery = new class ($database->table('items')) extends QueryProxy {
            protected function processFetchedRow(array $row)
            {
                return implode('|', $row);
            }
        };
        $superQuery->whereNotNull('value')->orderBy('id', 'desc');
        $this->assertEquals(['4|Bottle|0', '2|Apple|-10', '1|Banana|123'], $superQuery->get());
        $this->assertEquals('4|Bottle|0', $superQuery->first());
    }

    /**
     * Tests errors handling
     */
    public function testErrors()
    {
        $query = new class extends Query {
            public function __construct() {}
            public function get(): array {
                throw new \Exception('Test get');
            }
            public function first() {
                throw new \Exception('Test first');
            }
        };
        $superQuery = new QueryProxy($query);

        $this->assertException(\Exception::class, function () use ($superQuery) {
            $superQuery->get();
        }, function (\Exception $exception) {
            $this->assertEquals('Test get', $exception->getMessage());
        });
        $this->assertException(\Exception::class, function () use ($superQuery) {
            $superQuery->first();
        }, function (\Exception $exception) {
            $this->assertEquals('Test first', $exception->getMessage());
        });
    }
}
