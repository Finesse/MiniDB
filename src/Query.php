<?php

namespace Finesse\MiniDB;

use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\IncorrectQueryException;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\MiniDB\Exceptions\InvalidReturnValueException;
use Finesse\MiniDB\Parts\InsertTrait;
use Finesse\MiniDB\Parts\RawHelpersTrait;
use Finesse\MiniDB\Parts\SelectTrait;
use Finesse\QueryScribe\PostProcessors\ExplicitTables;
use Finesse\QueryScribe\Query as BaseQuery;
use Finesse\QueryScribe\StatementInterface;

/**
 * Query builder. Builds SQL queries and performs them on a database.
 *
 * All the methods throw Finesse\MiniDB\Exceptions\ExceptionInterface.
 *
 * {@inheritDoc}
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
     * Updates the query target rows. Doesn't modify itself.
     *
     * @param mixed[]|\Closure[]|self[]|StatementInterface[] $values Fields to update. The indexes are the columns
     *     names, the values are the values.
     * @return int The number of updated rows
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     * @throws InvalidReturnValueException
     */
    public function update(array $values): int
    {
        try {
            $query = (clone $this)->addUpdate($values)->apply($this->database->getTablePrefixer());
            $compiled = $this->database->getGrammar()->compileUpdate($query);
            return $this->database->update($compiled->getSQL(), $compiled->getBindings());
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Deletes the query target rows. Doesn't modify itself.
     *
     * @return int The number of deleted rows
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     * @throws InvalidReturnValueException
     */
    public function delete(): int
    {
        try {
            $query = (clone $this)->setDelete()->apply($this->database->getTablePrefixer());
            $compiled = $this->database->getGrammar()->compileDelete($query);
            return $this->database->delete($compiled->getSQL(), $compiled->getBindings());
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Makes the query have explicit tables in the column names.
     *
     * Warning! In contrast to the other methods, it doesn't modify the query object, it returns a new object.
     *
     * @return static
     */
    public function addTablesToColumnNames(): self
    {
        return $this->apply(new ExplicitTables);
    }

    /**
     * {@inheritDoc}
     */
    protected function constructEmptyCopy(): BaseQuery
    {
        return new static($this->database);
    }

    /**
     * {@inheritDoc}
     */
    protected function handleException(\Throwable $exception)
    {
        try {
            return parent::handleException($exception);
        } catch (\Throwable $exception) {
            throw Helpers::wrapException($exception);
        }
    }
}
