<?php

namespace Finesse\MiniDB\QueryParts;

use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\IncorrectQueryException;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\MiniDB\Query;
use Finesse\QueryScribe\StatementInterface;

/**
 * Contains methods for performing insert queries with Query.
 *
 * @author Surgie
 */
trait InsertTrait
{
    /**
     * Inserts rows to a table.
     *
     * @param mixed[][]|\Closure[][]|Query[][]|StatementInterface[][] $rows An array of rows. Each row is an associative
     *     array where indexes are column names and values are cell values. Rows indexes must be strings.
     * @return int Number of inserted rows
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     */
    public function insert(array $rows): int
    {
        return $this->performQuery(function () use ($rows) {
            $this->addInsert($rows);
            $count = 0;
            $statements = $this->database->getGrammar()->compileInsert($this);
            foreach ($statements as $statement) {
                $count += $this->database->insert($statement->getSQL(), $statement->getBindings());
            }
            return $count;
        });
    }

    /**
     * Inserts a row to a table and returns the inserted row identifier.
     *
     * @param mixed[]|\Closure[]|Query[]|StatementInterface[] $rows Row. Associative array where indexes are column
     *     names and values are cell values. Rows indexes must be strings.
     * @param string|null $sequence Name of the sequence object from which the ID should be returned
     * @return int|string
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     */
    public function insertGetId(array $row, string $sequence = null)
    {
        return $this->performQuery(function () use ($row, $sequence) {
            $this->addInsert($row);
            $statements = $this->database->getGrammar()->compileInsert($this);
            $id = null;
            foreach ($statements as $statement) {
                $id = $this->database->insertGetId($statement->getSQL(), $statement->getBindings(), $sequence);
            }
            return $id;
        });
    }

    /**
     * Inserts rows to a table from a select query.
     *
     * @param string[]|\Closure|Query|StatementInterface $columns The list of the columns to which the selected values
     *     should be inserted. You may omit this argument and pass the $selectQuery argument instead.
     * @param \Closure|self|StatementInterface|null $selectQuery
     * @return int Number of inserted rows
     * @throws DatabaseException
     * @throws IncorrectQueryException
     * @throws InvalidArgumentException
     */
    public function insertFromSelect($columns, $selectQuery = null): int
    {
        return $this->performQuery(function () use ($columns, $selectQuery) {
            $this->addInsertFromSelect($columns, $selectQuery);
            return $this->insert([]);
        });
    }
}
