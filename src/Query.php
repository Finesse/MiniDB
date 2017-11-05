<?php

namespace Finesse\MiniDB;

use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\ExceptionInterface;
use Finesse\MiniDB\Exceptions\IncorrectQueryException;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\MiniDB\QueryParts\InsertTrait;
use Finesse\MiniDB\QueryParts\SelectTrait;
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
     * Updates the query target rows. Doesn't modify itself.
     *
     * @param mixed[]|\Closure[]|self[]|StatementInterface[] $values Fields to update. The indexes are the columns
     *     names, the values are the values.
     * @return int The number of updated rows
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     */
    public function update(array $values): int
    {
        return $this->performQuery(function () use ($values) {
            $query = (clone $this)->addUpdate($values);
            $query = $this->database->getTablePrefixer()->process($query);
            $compiled = $this->database->getGrammar()->compileUpdate($query);
            return $this->database->update($compiled->getSQL(), $compiled->getBindings());
        });
    }

    /**
     * Deletes the query target rows. Doesn't modify itself.
     *
     * @return int The number of deleted rows
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     */
    public function delete(): int
    {
        return $this->performQuery(function () {
            $query = (clone $this)->setDelete();
            $query = $this->database->getTablePrefixer()->process($query);
            $compiled = $this->database->getGrammar()->compileDelete($query);
            return $this->database->delete($compiled->getSQL(), $compiled->getBindings());
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
