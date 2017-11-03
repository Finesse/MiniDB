<?php

namespace Finesse\MiniDB\Exceptions;

use Finesse\MicroDB\Exceptions\PDOException;

/**
 * Database driver thrown an error.
 *
 * @author Surgie
 */
class DatabaseException extends PDOException implements ExceptionInterface
{
    /**
     * {@inheritDoc}
     */
    public function getQuery(): string
    {
        return $this->getPrevious()->getQuery();
    }

    /**
     * {@inheritDoc}
     */
    public function getValues(): array
    {
        return $this->getPrevious()->getValues();
    }
}
