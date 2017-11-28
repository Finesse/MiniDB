<?php

namespace Finesse\MiniDB\Parts;

use Finesse\MiniDB\Database;
use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\IncorrectQueryException;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\MiniDB\Exceptions\InvalidReturnValueException;
use Finesse\QueryScribe\StatementInterface;

/**
 * Contains methods for performing select queries with Query.
 *
 * @author Surgie
 */
trait SelectTrait
{
    /**
     * @var Database Database on which the query should be performed
     */
    protected $database;

    /**
     * Performs a select query and returns the selected rows. Doesn't modify itself.
     *
     * @return array[] Array of the result rows. Result row is an array indexed by columns.
     * @throws DatabaseException
     * @throws IncorrectQueryException
     */
    public function get(): array
    {
        try {
            $query = $this->database->getTablePrefixer()->process($this);
            $compiled = $this->database->getGrammar()->compileSelect($query);
            return $this->database->select($compiled->getSQL(), $compiled->getBindings());
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
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
        try {
            $query = (clone $this)->limit(1);
            $query = $this->database->getTablePrefixer()->process($query);
            $compiled = $this->database->getGrammar()->compileSelect($query);
            return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings());
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Gets the count of the target rows. Doesn't modify itself.
     *
     * @param string|\Closure|self|StatementInterface $column Column to count
     * @return int
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     * @throws InvalidReturnValueException
     */
    public function count($column = '*'): int
    {
        try {
            $query = clone $this;
            $query->select = [];
            $query->addCount($column, 'aggregate')->offset(null)->limit(null);
            $query = $this->database->getTablePrefixer()->process($query);
            $compiled = $this->database->getGrammar()->compileSelect($query);
            return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Gets the average value of the target rows. Doesn't modify itself.
     *
     * @param string|\Closure|self|StatementInterface $column Column to get average
     * @return float|null Null is returned when no target row has a value
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     * @throws InvalidReturnValueException
     */
    public function avg($column)
    {
        try {
            $query = clone $this;
            $query->select = [];
            $query->addAvg($column, 'aggregate')->offset(null)->limit(null);
            $query = $this->database->getTablePrefixer()->process($query);
            $compiled = $this->database->getGrammar()->compileSelect($query);
            return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Gets the sum of the target rows. Doesn't modify itself.
     *
     * @param string|\Closure|self|StatementInterface $column Column to get sum
     * @return float|null Null is returned when no target row has a value
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     * @throws InvalidReturnValueException
     */
    public function sum($column)
    {
        try {
            $query = clone $this;
            $query->select = [];
            $query->addSum($column, 'aggregate')->offset(null)->limit(null);
            $query = $this->database->getTablePrefixer()->process($query);
            $compiled = $this->database->getGrammar()->compileSelect($query);
            return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Gets the minimum value of the target rows. Doesn't modify itself.
     *
     * @param string|\Closure|self|StatementInterface $column Column to get minimum
     * @return float|null Null is returned when no target row has a value
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     * @throws InvalidReturnValueException
     */
    public function min($column)
    {
        try {
            $query = clone $this;
            $query->select = [];
            $query->addMin($column, 'aggregate')->offset(null)->limit(null);
            $query = $this->database->getTablePrefixer()->process($query);
            $compiled = $this->database->getGrammar()->compileSelect($query);
            return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Gets the maximum value of the target rows. Doesn't modify itself.
     *
     * @param string|\Closure|self|StatementInterface $column Column to get maximum
     * @return float|null Null is returned when no target row has a value
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     * @throws InvalidReturnValueException
     */
    public function max($column)
    {
        try {
            $query = clone $this;
            $query->select = [];
            $query->addMax($column, 'aggregate')->offset(null)->limit(null);
            $query = $this->database->getTablePrefixer()->process($query);
            $compiled = $this->database->getGrammar()->compileSelect($query);
            return $this->database->selectFirst($compiled->getSQL(), $compiled->getBindings())['aggregate'];
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
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
            $this->handleException(new InvalidArgumentException('Chunk size must be greater than zero'));
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
