<?php

namespace Finesse\MiniDB;

use Finesse\MicroDB\Connection;
use Finesse\MicroDB\Exceptions\InvalidArgumentException as ConnectionInvalidArgumentException;
use Finesse\MicroDB\Exceptions\PDOException as ConnectionPDOException;
use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\ExceptionInterface;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\QueryScribe\Exceptions\InvalidArgumentException as QueryScribeInvalidArgumentException;
use Finesse\QueryScribe\Exceptions\InvalidReturnValueException as QueryScribeInvalidReturnValueException;
use Finesse\QueryScribe\GrammarInterface;
use Finesse\QueryScribe\Grammars\CommonGrammar;
use Finesse\QueryScribe\Grammars\MySQLGrammar;
use Finesse\QueryScribe\Grammars\SQLiteGrammar;
use Finesse\QueryScribe\MakeRawTrait;
use Finesse\QueryScribe\PostProcessors\TablePrefixer;
use Finesse\QueryScribe\StatementInterface;

/**
 * Database facade.
 *
 * @author Surgie
 */
class Database
{
    use MakeRawTrait;

    /**
     * @var Connection Database connection
     */
    protected $connection;

    /**
     * @var GrammarInterface Grammar (compiles queries to SQL)
     */
    protected $grammar;

    /**
     * @var TablePrefixer Table prefixer
     */
    protected $tablePrefixer;

    /**
     * The object doesn't change any given object.
     *
     * @param Connection $connection Database connection
     * @param GrammarInterface|null $grammar Grammar (compiles queries to SQL)
     * @param TablePrefixer $tablePrefixer Tables prefixer (prefixes are not prepended in raw SQL queries)
     */
    public function __construct(
        Connection $connection,
        GrammarInterface $grammar = null,
        TablePrefixer $tablePrefixer = null
    ) {
        $this->connection = $connection;
        $this->grammar = $grammar ?? new CommonGrammar();
        $this->tablePrefixer = $tablePrefixer ?? new TablePrefixer('');
    }

    /**
     * Makes a self instance from a configuration array. Parameters:
     *  * driver (optional) - DB driver name (for example, `mysql`, `sqlite`);
     *  * dsn - PDO data source name (DSN);
     *  * username (optional) - PDO username;
     *  * password (optional) - PDO password;
     *  * options (options) - array of options for PDO;
     *  * prefix (optional) - tables prefix (not prepended in raw SQL queries)
     *
     * @param array $config
     * @return static
     * @throws DatabaseException
     */
    public static function create(array $config): self
    {
        try {
            $connection = Connection::create(
                $config['dsn'] ?? '',
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

        return new static($connection, $grammar, new TablePrefixer($config['prefix'] ?? ''));
    }

    /**
     * Makes a query builder instance with a selected table.
     *
     * @param string|\Closure|Query|StatementInterface $table Not prefixed table name without quotes
     * @param string|null $alias Table alias. Warning! Alias is not allowed in insert, update and delete queries in some
     *     of the DBMSs.
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
     * Adds the table prefix to a table name.
     *
     * @param string $table Table name without quotes
     * @return string Table name with prefix
     */
    public function addTablePrefix(string $table): string
    {
        return $this->tablePrefixer->addTablePrefix($table);
    }

    /**
     * Adds the table prefix to a column name which may contain table name or alias.
     *
     * @param string $column Column name without quotes
     * @return string Column name with prefixed table name
     */
    public function addTablePrefixToColumn(string $column): string
    {
        return $this->tablePrefixer->addTablePrefixToColumn($column);
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
     * @return TablePrefixer Tables prefixer
     */
    public function getTablePrefixer(): TablePrefixer
    {
        return $this->tablePrefixer;
    }

    /**
     * Makes a self copy with the same dependencies instances but with another table prefix.
     *
     * @param string $prefix Table prefix
     * @return static
     */
    public function withTablePrefix(string $prefix): self
    {
        return new static($this->connection, $this->grammar, new TablePrefixer($prefix));
    }

    /**
     * Makes a self copy with the same dependencies instances but with added table prefix.
     *
     * The table prefix is not replaced, it is prefixed. For example, if an instance table prefix is `demo_`, calling
     * this method with the argument `test_` will make an instance with `test_demo_` table prefix.
     *
     * @param string $prefix Table prefix
     * @return static
     */
    public function withTablesPrefixed(string $prefix): self
    {
        return new static(
            $this->connection,
            $this->grammar,
            new TablePrefixer($prefix.$this->tablePrefixer->tablePrefix)
        );
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
