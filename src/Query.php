<?php

namespace Finesse\MiniDB;

use Finesse\MiniDB\Exceptions\ExceptionInterface;
use Finesse\MiniDB\Exceptions\IncorrectQueryException;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\QueryScribe\Exceptions\InvalidArgumentException as QueryScribeInvalidArgumentException;
use Finesse\QueryScribe\Exceptions\InvalidQueryException as QueryScribeInvalidQueryException;
use Finesse\QueryScribe\Query as BaseQuery;
use Finesse\QueryScribe\StatementInterface;

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
            $this->limit(1);
            $compiled = $this->database->getGrammar()->compileSelect($this);
            return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings());
        });
    }

    /**
     * Gets the count of the target rows.
     *
     * @param string|\Closure|self|StatementInterface $column Column to count
     * @return int
     */
    public function count($column = '*'): int
    {
        return $this->performQuery(function () use ($column) {
            $this->select = [];
            $this->addCount($column, 'aggregate')->offset(null)->limit(null);
            $compiled = $this->database->getGrammar()->compileSelect($this);
            return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
        });
    }

    /**
     * Gets the average value of the target rows.
     *
     * @param string|\Closure|self|StatementInterface $column Column to get average
     * @return float|null Null is returned when no target row has a value
     */
    public function avg($column)
    {
        return $this->performQuery(function () use ($column) {
            $this->select = [];
            $this->addAvg($column, 'aggregate')->offset(null)->limit(null);
            $compiled = $this->database->getGrammar()->compileSelect($this);
            return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
        });
    }

    /**
     * Gets the sum of the target rows.
     *
     * @param string|\Closure|self|StatementInterface $column Column to get sum
     * @return float|null Null is returned when no target row has a value
     */
    public function sum($column)
    {
        return $this->performQuery(function () use ($column) {
            $this->select = [];
            $this->addSum($column, 'aggregate')->offset(null)->limit(null);
            $compiled = $this->database->getGrammar()->compileSelect($this);
            return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
        });
    }

    /**
     * Gets the minimum value of the target rows.
     *
     * @param string|\Closure|self|StatementInterface $column Column to get minimum
     * @return float|null Null is returned when no target row has a value
     */
    public function min($column)
    {
        return $this->performQuery(function () use ($column) {
            $this->select = [];
            $this->addMin($column, 'aggregate')->offset(null)->limit(null);
            $compiled = $this->database->getGrammar()->compileSelect($this);
            return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
        });
    }

    /**
     * Gets the maximum value of the target rows.
     *
     * @param string|\Closure|self|StatementInterface $column Column to get maximum
     * @return float|null Null is returned when no target row has a value
     */
    public function max($column)
    {
        return $this->performQuery(function () use ($column) {
            $this->select = [];
            $this->addMax($column, 'aggregate')->offset(null)->limit(null);
            $compiled = $this->database->getGrammar()->compileSelect($this);
            return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
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
        } catch (QueryScribeInvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
        } catch (QueryScribeInvalidQueryException $exception) {
            throw new IncorrectQueryException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
