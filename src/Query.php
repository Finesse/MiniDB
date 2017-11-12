<?php

namespace Finesse\MiniDB;

use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\IncorrectQueryException;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\MiniDB\Parts\InsertTrait;
use Finesse\MiniDB\Parts\RawHelpersTrait;
use Finesse\MiniDB\Parts\SelectTrait;
use Finesse\QueryScribe\Exceptions\InvalidArgumentException as QueryScribeInvalidArgumentException;
use Finesse\QueryScribe\Exceptions\InvalidQueryException as QueryScribeInvalidQueryException;
use Finesse\QueryScribe\Exceptions\InvalidReturnValueException as QueryScribeInvalidReturnValueException;
use Finesse\QueryScribe\Query as BaseQuery;
use Finesse\QueryScribe\StatementInterface;

/**
 * Query builder. Builds SQL queries and performs them on a database.
 *
 * All the methods throw Finesse\MiniDB\Exceptions\InvalidArgumentException exceptions if not specified explicitly.
 *
 * @author Surgie
 */
class Query extends BaseQuery
{
    use SelectTrait, InsertTrait, RawHelpersTrait;

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
        $query = (clone $this)->addUpdate($values);
        $query = $this->database->getTablePrefixer()->process($query);

        try {
            $compiled = $this->database->getGrammar()->compileUpdate($query);
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }

        return $this->database->update($compiled->getSQL(), $compiled->getBindings());
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
        $query = (clone $this)->setDelete();
        $query = $this->database->getTablePrefixer()->process($query);

        try {
            $compiled = $this->database->getGrammar()->compileDelete($query);
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }

        return $this->database->delete($compiled->getSQL(), $compiled->getBindings());
    }

    /**
     * {@inheritDoc}
     */
    protected function handleException(\Throwable $exception)
    {
        if (
            $exception instanceof QueryScribeInvalidArgumentException ||
            $exception instanceof QueryScribeInvalidReturnValueException
        ) {
            throw new InvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if ($exception instanceof QueryScribeInvalidQueryException) {
            throw new IncorrectQueryException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return parent::handleException($exception);
    }


}
