<?php

namespace Finesse\MiniDB;

use Finesse\MicroDB\Connection;
use Finesse\MicroDB\Exceptions\InvalidArgumentException as ConnectionInvalidArgumentException;
use Finesse\MicroDB\Exceptions\PDOException as ConnectionPDOException;
use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\ExceptionInterface;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\QueryScribe\AddTablePrefixTrait;
use Finesse\QueryScribe\Exceptions\InvalidArgumentException as QueryScribeInvalidArgumentException;
use Finesse\QueryScribe\Exceptions\InvalidReturnValueException as QueryScribeInvalidReturnValueException;
use Finesse\QueryScribe\GrammarInterface;
use Finesse\QueryScribe\Grammars\CommonGrammar;
use Finesse\QueryScribe\Grammars\MySQLGrammar;
use Finesse\QueryScribe\Grammars\SQLiteGrammar;
use Finesse\QueryScribe\MakeRawTrait;
use Finesse\QueryScribe\StatementInterface;

/**
 * Database facade.
 *
 * @author Surgie
 */
class Database
{
    use AddTablePrefixTrait, MakeRawTrait;

    /**
     * @var Connection Database connection
     */
    protected $connection;

    /**
     * @var GrammarInterface Grammar (compiles queries to SQL)
     */
    protected $grammar;

    /**
     * The object doesn't change any given object.
     *
     * @param Connection $connection Database connection
     * @param GrammarInterface|null $grammar Grammar (compiles queries to SQL)
     * @param string $tablePrefix Tables prefix (not prepended in raw SQL queries)
     */
    public function __construct(Connection $connection, GrammarInterface $grammar = null, string $tablePrefix = '')
    {
        $this->connection = $connection;
        $this->grammar = $grammar ?? new CommonGrammar();
        $this->tablePrefix = $tablePrefix;
    }

    /**
     * Makes a self instance from a configuration array. Parameters:
     *  * driver (optional) - DB driver name (for example, `mysql`, `sqlite`);
     *  * dns - PDO dns string;
     *  * username (optional) - PDO username;
     *  * password (optional) - PDO password;
     *  * options (options) - array of options for PDO;
     *  * prefix (optional) - tables prefix (not prepended in raw SQL queries)
     *
     * @param array $config
     * @return self
     * @throws DatabaseException
     */
    public static function create(array $config): self
    {
        try {
            $connection = Connection::create(
                $config['dns'] ?? '',
                $config['username'] ?? null,
                $config['password'] ?? null,
                $config['options'] ?? null
            );
        } catch (ConnectionPDOException $exception) {
            throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
        }

        switch (strtolower($config['driver'] ?? '')) {
            case 'mysql':
                $grammar = new MySQLGrammar();
                break;
            case 'sqlite':
                $grammar = new SQLiteGrammar();
                break;
            default:
                $grammar = null;
        }

        return new static($connection, $grammar, $config['prefix'] ?? '');
    }

    /**
     * Makes a query builder instance with a selected table.
     *
     * @param string|\Closure|Query|StatementInterface $table Not prefixed table name without quotes
     * @param string|null $alias Table alias
     * @return Query
     * @throws InvalidArgumentException
     */
    public function table($table, string $alias = null): Query
    {
        try {
            return (new Query($this))->table($table, $alias);
        } catch (QueryScribeInvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
        } catch (QueryScribeInvalidReturnValueException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

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
        return $this->performQuery(function () use ($query, $values) {
            return $this->connection->select($query, $values);
        });
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
        return $this->performQuery(function () use ($query, $values) {
            return $this->connection->selectFirst($query, $values);
        });
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
        return $this->performQuery(function () use ($query, $values) {
            return $this->connection->insert($query, $values);
        });
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
        return $this->performQuery(function () use ($query, $values, $sequence) {
            return $this->connection->insertGetId($query, $values, $sequence);
        });
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
        return $this->performQuery(function () use ($query, $values) {
            return $this->connection->update($query, $values);
        });
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
        return $this->performQuery(function () use ($query, $values) {
            return $this->connection->delete($query, $values);
        });
    }

    /**
     * Performs a general query.
     *
     * @param string $query Full SQL query (tables are not prefixed here)
     * @param array $values Values to bind. The indexes are the names or numbers of the values.
     * @throws InvalidArgumentException
     * @throws DatabaseException
     */
    public function statement(string $query, array $values = [])
    {
        $this->performQuery(function () use ($query, $values) {
            $this->connection->statement($query, $values);
        });
    }

    /**
     * @return Connection Underlying connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return GrammarInterface Underlying grammar
     */
    public function getGrammar(): GrammarInterface
    {
        return $this->grammar;
    }

    /**
     * @return string Tables prefix
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * Performs a database query and handles exceptions.
     *
     * @param \Closure $callback Function that performs the query
     * @return mixed The $callback return value
     * @return ExceptionInterface|\Throwable
     */
    protected function performQuery(\Closure $callback)
    {
        try {
            return $callback();
        } catch (ConnectionPDOException $exception) {
            throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
        } catch (ConnectionInvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
