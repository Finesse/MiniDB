<?php

namespace Finesse\MiniDB\Parts;

use Finesse\MiniDB\Database;
use Finesse\QueryScribe\GrammarInterface;
use Finesse\QueryScribe\PostProcessors\TablePrefixer;

/**
 * Contains helper methods for working with SQL queries. Suitable both for Database and Query.
 *
 * @author Surgie
 */
trait RawHelpersTrait
{
    /**
     * @var GrammarInterface|null Grammar (compiles queries to SQL)
     */
    protected $grammar;

    /**
     * @var TablePrefixer|null Table prefixer
     */
    protected $tablePrefixer;

    /**
     * @var Database Database on which the query should be performed
     */
    protected $database;

    /**
     * Adds the table prefix to a table name.
     *
     * @param string $table Table name without quotes
     * @return string Table name with prefix
     */
    public function addTablePrefix(string $table): string
    {
        return ($this->tablePrefixer ?? $this->database->getTablePrefixer())->addTablePrefix($table);
    }

    /**
     * Adds the table prefix to a column name which may contain table name or alias.
     *
     * @param string $column Column name without quotes
     * @return string Column name with prefixed table name
     */
    public function addTablePrefixToColumn(string $column): string
    {
        return ($this->tablePrefixer ?? $this->database->getTablePrefixer())->addTablePrefixToColumn($column);
    }

    /**
     * Wraps a identifier (table name, column, database, etc.) with quotes. Considers . (split) and * (all columns), for
     * example `table.*`.
     *
     * @param string $identifier
     * @return string
     */
    public function quoteCompositeIdentifier(string $identifier): string
    {
        return ($this->grammar ?? $this->database->getGrammar())->quoteCompositeIdentifier($identifier);
    }

    /**
     * Wraps a plain (without nesting by dots) identifier (table name, column, database, etc.) with quotes and screens
     * inside quotes. Must wrap everything even . and *.
     *
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier(string $identifier): string
    {
        return ($this->grammar ?? $this->database->getGrammar())->quoteIdentifier($identifier);
    }

    /**
     * Escapes the LIKE operator special characters. Doesn't escape general string wildcard characters because it is
     * another job.
     *
     * @param string $string
     * @return string
     */
    public function escapeLikeWildcards(string $string): string
    {
        return ($this->grammar ?? $this->database->getGrammar())->escapeLikeWildcards($string);
    }
}
