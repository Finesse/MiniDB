<?php

namespace Finesse\MiniDB;

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
use Finesse\QueryScribe\Exceptions\ExceptionInterface as QueryScribeException;
use Finesse\QueryScribe\Exceptions\InvalidArgumentException as QueryScribeInvalidArgumentException;
use Finesse\QueryScribe\Exceptions\InvalidQueryException as QueryScribeInvalidQueryException;
use Finesse\QueryScribe\Exceptions\InvalidReturnValueException as QueryScribeInvalidReturnValueException;

/**
 * Helper functions
 *
 * @author Surgie
 */
class Helpers
{
    /**
     * Turns the given exception to a this package exception (if possible).
     *
     * @param \Throwable $exception
     * @return ExceptionInterface|\Throwable
     */
    public static function wrapException(\Throwable $exception): \Throwable
    {
        if ($exception instanceof ExceptionInterface) {
            return $exception;
        }

        if ($exception instanceof ConnectionException) {
            if ($exception instanceof ConnectionPDOException) {
                return new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
            }
            if ($exception instanceof ConnectionInvalidArgumentException) {
                return new InvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
            }
            if ($exception instanceof ConnectionFileException) {
                return new FileException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        if ($exception instanceof QueryScribeException) {
            if ($exception instanceof QueryScribeInvalidArgumentException) {
                return new InvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
            }
            if ($exception instanceof QueryScribeInvalidReturnValueException) {
                return new InvalidReturnValueException($exception->getMessage(), $exception->getCode(), $exception);
            }
            if ($exception instanceof QueryScribeInvalidQueryException) {
                return new IncorrectQueryException($exception->getMessage(), $exception->getCode(), $exception);
            }
        }

        return $exception;
    }
}
