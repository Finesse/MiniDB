<?php

namespace Finesse\MiniDB;

use Finesse\MiniDB\Exceptions\ExceptionInterface;
use Finesse\MiniDB\Exceptions\IncorrectQueryException;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\MiniDB\QueryParts\InsertTrait;
use Finesse\MiniDB\QueryParts\SelectTrait;
use Finesse\QueryScribe\Exceptions\InvalidArgumentException as QueryScribeInvalidArgumentException;
use Finesse\QueryScribe\Exceptions\InvalidQueryException as QueryScribeInvalidQueryException;
use Finesse\QueryScribe\Query as BaseQuery;

/**
 * Query builder. Builds SQL queries and performs them on a database.
 *
 * @author Surgie
 */
class Query extends BaseQuery
{
    use SelectTrait, InsertTrait;

    /**
     * @var Database Database on which the query should be performed
     */
    protected $database;

    /**
     * @param Database $database Database on which the query should be performed
     */
    public function __construct(Database $database)
    {
        parent::__construct($database->getTablePrefix());
        $this->database = $database;
    }

    /**
     * {@inheritDoc}
     */
    public function makeEmptyCopy(): BaseQuery
    {
        return new static($this->database);
    }

    /**
     * Performs a query compilation with database query and handles exceptions.
     *
     * @param \Closure $callback Function that performs the compilation with query
     * @return mixed The $callback return value
     * @return ExceptionInterface|\Throwable
     */
    protected function performQuery(\Closure $callback)
    {
        try {
            return $callback();
        } catch (QueryScribeInvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
        } catch (QueryScribeInvalidQueryException $exception) {
            throw new IncorrectQueryException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
