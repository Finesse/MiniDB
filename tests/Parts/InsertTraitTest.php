<?php

namespace Finesse\MiniDB\Tests\Parts;

use Finesse\MiniDB\Database;
use Finesse\MiniDB\Exceptions\IncorrectQueryException;
use Finesse\MiniDB\Query;
use Finesse\MiniDB\Tests\TestCase;

/**
 * Tests the InsertTrait trait
 *
 * @author Surgie
 */
class InsertTraitTest extends TestCase
{
    /**
     * Tests all the methods method
     */
    public function testInsert()
    {
        $database = Database::create(['driver' => 'sqlite', 'dsn' => 'sqlite::memory:', 'prefix' => 'test_']);
        $database->statement('CREATE TABLE '.$database->addTablePrefix('users')
            . '(id INTEGER PRIMARY KEY ASC, name TEXT, address TEXT)');

        // Insert and get inserted count
        $this->assertEquals(3, $database->table('users')->insert([
            ['address' => '123 Sesame str.', 'name' => 'Cookie Monster'],
            ['name' => 'John Doe'],
            ['address' => '1 Main avenue']
        ]));
        $this->assertEquals([
            ['id' => 1, 'name' => 'Cookie Monster', 'address' => '123 Sesame str.'],
            ['id' => 2, 'name' => 'John Doe', 'address' => null],
            ['id' => 3, 'name' => null, 'address' => '1 Main avenue']
        ], $database->select('SELECT * FROM '.$database->addTablePrefix('users').' ORDER BY id'));

        // Insert and get inserted id
        $this->assertEquals(4, $database->table('users')->insertGetId(['name' => 'Ninja', 'address' => 'Japan']));
        $this->assertEquals(
            ['id' => 4, 'name' => 'Ninja', 'address' => 'Japan'],
            $database->selectFirst('SELECT * FROM '.$database->addTablePrefix('users').' WHERE id = ?', [4])
        );

        // Insert from select
        $this->assertEquals(2, $database->table('users')->insertFromSelect(['address', 'name'], function ($query) {
            $this->assertInstanceOf(Query::class, $query);
            $query->addSelect(['address', 'name'])->from('users')->where('id', '<', 3);
        }));
        $this->assertEquals([
            ['id' => 5, 'name' => 'Cookie Monster', 'address' => '123 Sesame str.'],
            ['id' => 6, 'name' => 'John Doe', 'address' => null]
        ], $database->select('SELECT * FROM '.$database->addTablePrefix('users').' WHERE id > ? ORDER BY id', [4]));

        // Incorrect query error
        $this->assertException(IncorrectQueryException::class, function () use ($database) {
            (new Query($database))->insert(['foo' => 'bar']);
        });
        $this->assertException(IncorrectQueryException::class, function () use ($database) {
            (new Query($database))->insertGetId(['foo' => 'bar']);
        });
    }
}
