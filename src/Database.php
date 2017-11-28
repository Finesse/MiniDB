<?php

namespace Finesse\MiniDB;

use Finesse\MicroDB\Connection;
use Finesse\MicroDB\Exceptions\FileException as ConnectionFileException;
use Finesse\MicroDB\Exceptions\InvalidArgumentException as ConnectionInvalidArgumentException;
use Finesse\MicroDB\Exceptions\PDOException as ConnectionPDOException;
use Finesse\MicroDB\IException as ConnectionException;
use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\ExceptionInterface;
use Finesse\MiniDB\Exceptions\FileException;
use Finesse\MiniDB\Exceptions\IncorrectQueryException;
use Finesse\MiniDB\Exceptions\InvalidArgumentException;
use Finesse\MiniDB\Exceptions\InvalidReturnValueException;
use Finesse\MiniDB\Parts\RawHelpersTrait;
use Finesse\MiniDB\Parts\RawStatementsTrait;
use Finesse\QueryScribe\Exceptions\ExceptionInterface as QueryScribeException;
use Finesse\QueryScribe\Exceptions\InvalidArgumentException as QueryScribeInvalidArgumentException;
use Finesse\QueryScribe\Exceptions\InvalidQueryException as QueryScribeInvalidQueryException;
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
    use MakeRawTrait, RawStatementsTrait, RawHelpersTrait;

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
     * Makes an empty query instance. You should specify a table on the returned query, otherwise you won't be able to
     * perform the query on the database.
     *
     * @ignore It is the only place where the package makes query builder instances. So if you need to extend this class
     *     and use a custom query builder class, you have to override only this method.
     *
     * @return Query
     */
    public function builder(): Query
    {
        return new Query($this);
    }

    /**
     * Makes a query builder instance with a selected table.
     *
     * @param string|\Closure|Query|StatementInterface $table Not prefixed table name without quotes
     * @param string|null $alias Table alias. Warning! Alias is not allowed in insert, update and delete queries in some
     *     of the DBMSs.
     * @return Query
     * @throws InvalidArgumentException
     * @throws InvalidReturnValueException
     */
    public function table($table, string $alias = null): Query
    {
        return $this->builder()->table($table, $alias);
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
     * Handles an exception thrown by this class.
     *
     * @param \Throwable $exception The thrown exception
     * @return mixed A value to return
     * @throws \Throwable
     */
    public function handleException(\Throwable $exception)
    {
        if ($exception instanceof ExceptionInterface) {
            throw $exception;
        }

        if ($exception instanceof ConnectionException) {
            if ($exception instanceof ConnectionPDOException) {
                throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
            }
            if ($exception instanceof ConnectionInvalidArgumentException) {
                throw new InvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
            }
            if ($exception instanceof ConnectionFileException) {
                throw new FileException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        if ($exception instanceof QueryScribeException) {
            if ($exception instanceof QueryScribeInvalidArgumentException) {
                throw new InvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
            }
            if ($exception instanceof QueryScribeInvalidReturnValueException) {
                throw new InvalidReturnValueException($exception->getMessage(), $exception->getCode(), $exception);
            }
            if ($exception instanceof QueryScribeInvalidQueryException) {
                throw new IncorrectQueryException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        throw $exception;
    }
}
