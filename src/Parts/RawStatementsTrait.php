<?php

namespace Finesse\MiniDB\Parts;

use Finesse\MicroDB\Connection;
use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\FileException;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;

/**
 * Contains methods for performing raw SQL queries.
 *
 * @author Surgie
 */
trait RawStatementsTrait
{
    /**
     * @var Connection Database connection
     */
    protected $connection;

    /**
     * Performs a select query and returns the query results.
     *
     * @param string $query Full SQL query (tables are not prefixed here)
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @return array[] Array of the result rows. Result row is an array indexed by columns.
     * @throws InvalidArgumentException
     * @throws DatabaseException
     */
    public function select(string $query, array $values = []): array
    {
        try {
            return $this->connection->select($query, $values);
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Performs a select query and returns the first query result.
     *
     * @param string $query Full SQL query (tables are not prefixed here)
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @return array|null An array indexed by columns. Null if nothing is found.
     * @throws InvalidArgumentException
     * @throws DatabaseException
     */
    public function selectFirst(string $query, array $values = [])
    {
        try {
            return $this->connection->selectFirst($query, $values);
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Performs a insert query and returns the number of inserted rows.
     *
     * @param string $query Full SQL query (tables are not prefixed here)
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @return int
     * @throws InvalidArgumentException
     * @throws DatabaseException
     */
    public function insert(string $query, array $values = []): int
    {
        try {
            return $this->connection->insert($query, $values);
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Performs a insert query and returns the identifier of the last inserted row.
     *
     * @param string $query Full SQL query (tables are not prefixed here)
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @param string|null $sequence Name of the sequence object from which the ID should be returned
     * @return int|string
     * @throws InvalidArgumentException
     * @throws DatabaseException
     */
    public function insertGetId(string $query, array $values = [], string $sequence = null)
    {
        try {
            return $this->connection->insertGetId($query, $values, $sequence);
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Performs an update query.
     *
     * @param string $query Full SQL query (tables are not prefixed here)
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @return int The number of updated rows
     * @throws InvalidArgumentException
     * @throws DatabaseException
     */
    public function update(string $query, array $values = []): int
    {
        try {
            return $this->connection->update($query, $values);
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Performs a delete query.
     *
     * @param string $query Full SQL query (tables are not prefixed here)
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @return int The number of deleted rows
     * @throws InvalidArgumentException
     * @throws DatabaseException
     */
    public function delete(string $query, array $values = []): int
    {
        try {
            return $this->connection->delete($query, $values);
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Performs a general query. If the query contains multiple statements separated by a semicolon, only the first
     * statement will be executed.
     *
     * @param string $query Full SQL query (tables are not prefixed here)
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @throws InvalidArgumentException
     * @throws DatabaseException
     */
    public function statement(string $query, array $values = [])
    {
        try {
            $this->connection->statement($query, $values);
        } catch (\Throwable $exception) {
            $this->handleException($exception);
        }
    }

    /**
     * Performs a general query. It executes all the statements separated by a semicolon.
     *
     * @param string $query Full SQL query (tables are not prefixed here)
     * @throws DatabaseException
     */
    public function statements(string $query)
    {
        try {
            $this->connection->statements($query);
        } catch (\Throwable $exception) {
            $this->handleException($exception);
        }
    }

    /**
     * Executes statements from a file.
     *
     * @param string|resource $file A file path or a read resource. If a resource is given, it will be read to the end
     *     end closed. Tables are not prefixed in the file query.
     * @throws DatabaseException
     * @throws InvalidArgumentException
     * @throws FileException
     */
    public function import($file)
    {
        try {
            $this->connection->import($file);
        } catch (\Throwable $exception) {
            $this->handleException($exception);
        }
    }
}
