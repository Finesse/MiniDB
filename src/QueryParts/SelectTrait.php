<?php

namespace Finesse\MiniDB\QueryParts;

use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\IncorrectQueryException;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\QueryScribe\Exceptions\InvalidQueryException as QueryScribeInvalidQueryException;
use Finesse\QueryScribe\StatementInterface;

/**
 * Contains methods for performing select queries with Query.
 *
 * @author Surgie
 */
trait SelectTrait
{
    /**
     * Performs a select query and returns the selected rows. Doesn't modify itself.
     *
     * @return array[] Array of the result rows. Result row is an array indexed by columns.
     * @throws DatabaseException
     * @throws IncorrectQueryException
     */
    public function get(): array
    {
        $query = $this->database->getTablePrefixer()->process($this);

        try {
            $compiled = $this->database->getGrammar()->compileSelect($query);
        } catch (QueryScribeInvalidQueryException $exception) {
            throw new IncorrectQueryException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->database->select($compiled->getSQL(), $compiled->getBindings());
    }

    /**
     * Performs a select query and returns the first selected row. Doesn't modify itself.
     *
     * @return array|null An array indexed by columns. Null if nothing is found.
     * @throws DatabaseException
     * @throws IncorrectQueryException
     */
    public function first()
    {
        $query = (clone $this)->limit(1);
        $query = $this->database->getTablePrefixer()->process($query);

        try {
            $compiled = $this->database->getGrammar()->compileSelect($query);
        } catch (QueryScribeInvalidQueryException $exception) {
            throw new IncorrectQueryException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings());
    }

    /**
     * Gets the count of the target rows. Doesn't modify itself.
     *
     * @param string|\Closure|self|StatementInterface $column Column to count
     * @return int
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     */
    public function count($column = '*'): int
    {
        $query = clone $this;
        $query->select = [];
        $query->addCount($column, 'aggregate')->offset(null)->limit(null);
        $query = $this->database->getTablePrefixer()->process($query);

        try {
            $compiled = $this->database->getGrammar()->compileSelect($query);
        } catch (QueryScribeInvalidQueryException $exception) {
            throw new IncorrectQueryException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
    }

    /**
     * Gets the average value of the target rows. Doesn't modify itself.
     *
     * @param string|\Closure|self|StatementInterface $column Column to get average
     * @return float|null Null is returned when no target row has a value
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     */
    public function avg($column)
    {
        $query = clone $this;
        $query->select = [];
        $query->addAvg($column, 'aggregate')->offset(null)->limit(null);
        $query = $this->database->getTablePrefixer()->process($query);

        try {
            $compiled = $this->database->getGrammar()->compileSelect($query);
        } catch (QueryScribeInvalidQueryException $exception) {
            throw new IncorrectQueryException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
    }

    /**
     * Gets the sum of the target rows. Doesn't modify itself.
     *
     * @param string|\Closure|self|StatementInterface $column Column to get sum
     * @return float|null Null is returned when no target row has a value
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     */
    public function sum($column)
    {
        $query = clone $this;
        $query->select = [];
        $query->addSum($column, 'aggregate')->offset(null)->limit(null);
        $query = $this->database->getTablePrefixer()->process($query);

        try {
            $compiled = $this->database->getGrammar()->compileSelect($query);
        } catch (QueryScribeInvalidQueryException $exception) {
            throw new IncorrectQueryException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
    }

    /**
     * Gets the minimum value of the target rows. Doesn't modify itself.
     *
     * @param string|\Closure|self|StatementInterface $column Column to get minimum
     * @return float|null Null is returned when no target row has a value
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     */
    public function min($column)
    {
        $query = clone $this;
        $query->select = [];
        $query->addMin($column, 'aggregate')->offset(null)->limit(null);
        $query = $this->database->getTablePrefixer()->process($query);

        try {
            $compiled = $this->database->getGrammar()->compileSelect($query);
        } catch (QueryScribeInvalidQueryException $exception) {
            throw new IncorrectQueryException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
    }

    /**
     * Gets the maximum value of the target rows. Doesn't modify itself.
     *
     * @param string|\Closure|self|StatementInterface $column Column to get maximum
     * @return float|null Null is returned when no target row has a value
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     */
    public function max($column)
    {
        $query = clone $this;
        $query->select = [];
        $query->addMax($column, 'aggregate')->offset(null)->limit(null);
        $query = $this->database->getTablePrefixer()->process($query);

        try {
            $compiled = $this->database->getGrammar()->compileSelect($query);
        } catch (QueryScribeInvalidQueryException $exception) {
            throw new IncorrectQueryException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
    }

    /**
     * Walks large amount of rows calling a callback on small portions of rows. Doesn't modify itself.
     *
     * @param int $size Number of rows per callback call
     * @param callable $callback The callback. Receives an array of rows as the first argument.
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     */
    public function chunk(int $size, callable $callback)
    {
        if ($size <= 0) {
            throw new InvalidArgumentException('Chunk size must be greater than zero');
        }

        // A copy is made not to mutate this query
        $query = clone $this;

        for ($offset = 0;; $offset += $size) {
            $rows = $query->offset($offset)->limit($size)->get();
            if (empty($rows)) {
                break;
            }

            $callback($rows);

            if (count($rows) < $size) {
                break;
            }
        }
    }
}
