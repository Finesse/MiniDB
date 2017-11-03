<?php

namespace Finesse\MiniDB\Tests\ThirdParty;

use Finesse\MiniDB\Database;
use Finesse\MiniDB\Tests\TestCase;
use Finesse\MiniDB\ThirdParty\PagerfantaAdapter;

/**
 * Tests the PagerfantaAdapter class
 *
 * @author Surgie
 */
class PagerfantaAdapterTest extends TestCase
{
    /**
     * Tests the whole adapter
     */
    public function testAdapter()
    {
        $database = Database::create(['driver' => 'sqlite', 'dns' => 'sqlite::memory:', 'prefix' => 'pre_']);
        $database->statement('CREATE TABLE pre_items(id INTEGER PRIMARY KEY ASC, name TEXT)');
        for ($i = 0; $i < 58; ++$i) {
            $database->insert('INSERT INTO pre_items (name) VALUES (?)', ['Item '.$i]);
        }

        $query = $database->table('items')->addSelect('name')->where('id', '>', 10)->orderBy('id');
        $adapter = new PagerfantaAdapter($query);

        $this->assertEquals(48, $adapter->getNbResults());
        $this->assertEquals([
            ['name' => 'Item 37'],
            ['name' => 'Item 38'],
            ['name' => 'Item 39'],
            ['name' => 'Item 40'],
            ['name' => 'Item 41'],
            ['name' => 'Item 42'],
            ['name' => 'Item 43'],
            ['name' => 'Item 44']
        ], $adapter->getSlice(27, 8));
        $this->assertEquals([
            ['name' => 'Item 55'],
            ['name' => 'Item 56'],
            ['name' => 'Item 57']
        ], $adapter->getSlice(45, 5));
    }
}
