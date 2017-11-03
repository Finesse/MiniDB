<?php

namespace Finesse\MiniDB;

use Finesse\MiniDB\Exceptions\ExceptionInterface;
use Finesse\MiniDB\Exceptions\IncorrectQueryException;
use Finesse\QueryScribe\Exceptions\InvalidQueryException as QueryScribeInvalidQueryException;
use Finesse\QueryScribe\Query as BaseQuery;

/**
 * Query builder. Builds SQL queries and performs them on a database.
 *
 * @author Surgie
 */
class Query extends BaseQuery
{
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
     * Performs a select query and returns the selected rows.
     *
     * @return array[] Array of the result rows. Result row is an array indexed by columns.
     * @throws ExceptionInterface
     */
    public function get(): array
    {
        return $this->performQuery(function () {
            $compiled = $this->database->getGrammar()->compileSelect($this);
            return $this->database->select($compiled->getSQL(), $compiled->getBindings());
        });
    }

    /**
     * Performs a select query and returns the first selected row.
     *
     * @return array|null An array indexed by columns. Null if nothing is found.
     * @throws ExceptionInterface
     */
    public function first()
    {
        return $this->performQuery(function () {
            $compiled = $this->database->getGrammar()->compileSelect($this->limit(1));
            return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings());
        });
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
        } catch (QueryScribeInvalidQueryException $exception) {
            throw new IncorrectQueryException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
