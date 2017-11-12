<?php

namespace Finesse\MiniDB\Tests\Parts;

use Finesse\MiniDB\Database;
use Finesse\MiniDB\Tests\TestCase;

class RawHelpersTraitTest extends TestCase
{
    /**
     * Tests the `addTablePrefix` and `addTablePrefixToColumn` methods
     */
    public function testAddTablePrefix()
    {
        $database = Database::create(['driver' => 'sqlite', 'dsn' => 'sqlite::memory:', 'prefix' => 'prefix_']);
        $query = $database->table('foo');

        $this->assertEquals('prefix_tab1', $database->addTablePrefix('tab1'));
        $this->assertEquals('database.prefix_table', $query->addTablePrefix('database.table'));

        $this->assertEquals('column1', $database->addTablePrefixToColumn('column1'));
        $this->assertEquals('prefix_table.column1', $database->addTablePrefixToColumn('table.column1'));
        $this->assertEquals('database.prefix_table.column1', $query->addTablePrefixToColumn('database.table.column1'));
    }

    /**
     * Tests the `quoteCompositeIdentifier` and `quoteIdentifier` methods
     */
    public function testQuoteIdentifier()
    {
        $database = Database::create(['driver' => 'sqlite', 'dsn' => 'sqlite::memory:', 'prefix' => 'prefix_']);
        $query = $database->table('foo');

        $this->assertEquals('"name"', $database->quoteIdentifier('name'));
        $this->assertEquals('"sub""name"', $database->quoteIdentifier('sub"name'));
        $this->assertEquals('"*"', $query->quoteIdentifier('*'));

        $this->assertEquals('"name"', $database->quoteCompositeIdentifier('name'));
        $this->assertEquals('"table".*', $database->quoteCompositeIdentifier('table.*'));
        $this->assertEquals(
            '"database"."table"."col""umn"',
            $query->quoteCompositeIdentifier('database.table.col"umn')
        );
    }

    /**
     * Tests the `escapeLikeWildcards` method.
     */
    public function testEscapeLikeWildcards()
    {
        $database = Database::create(['driver' => 'sqlite', 'dsn' => 'sqlite::memory:', 'prefix' => 'prefix_']);
        $database->statement('CREATE TABLE '.$database->addTablePrefix('items').'(id INTEGER PRIMARY KEY ASC, name TEXT)');
        $database->table('items')->insert([
            ['name' => 'foo_bar'],
            ['name' => 'foo%bar'],
            ['name' => 'foo\\n\\bar']
        ]);

        $this->assertEquals(3, $database
            ->table('items')
            ->where('name', 'like', 'foo%bar')
            ->count());

        $this->assertEquals([['name' => 'foo%bar']], $database
            ->table('items')
            ->addSelect('name')
            ->where('name', 'like', $database->escapeLikeWildcards('foo%bar'))
            ->get());

        $query = $database->table('items')->addSelect('name');
        $query->where('name', 'like', $query->escapeLikeWildcards('foo_bar'));
        $this->assertEquals([['name' => 'foo_bar']], $query->get());

        $this->assertEquals([['name' => 'foo\\n\\bar']], $database
            ->table('items')
            ->addSelect('name')
            ->where('name', 'like', $database->escapeLikeWildcards('foo\\n').'%')
            ->get());
    }
}
