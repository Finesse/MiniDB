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
     * Tests more error cases
     */
    public function testErrors()
    {
        $database = Database::create(['driver' => 'sqlite', 'dns' => 'sqlite::memory:', 'prefix' => 'pre_']);

        $this->assertException(IncorrectQueryException::class, function () use ($database) {
            $database->table('animals')->offset(10)->get();
        }, function (IncorrectQueryException $exception) {
            $this->assertInstanceOf(QueryScribeInvalidQueryException::class, $exception->getPrevious());
        });

        $this->assertException(DatabaseException::class, function () use ($database) {
            $database->table('animals')->get();
        });
    }
}
