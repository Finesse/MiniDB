<?php

namespace Finesse\MiniDB;

use Finesse\MiniDB\Parts\SelectTrait;
use Finesse\QueryScribe\QueryProxy as BaseQueryProxy;

/**
 * Helps to extend a query object dynamically.
 *
 * All the methods throw Finesse\MiniDB\Exceptions\ExceptionInterface.
 *
 * {@inheritDoc}
 *
 * @mixin Query
 *
 * @author Sugrie
 */
class QueryProxy extends BaseQueryProxy
{
    /**
     * {@inheritDoc}
     * @param Query $baseQuery
     */
    public function __construct(Query $baseQuery)
    {
        parent::__construct($baseQuery);
    }

    /**
     * {@inheritDoc}
     * @return mixed[]
     * @see SelectTrait::get
     */
    public function get(): array
    {
        try {
            $rows = $this->baseQuery->get();
            return array_map([$this, 'processFetchedRow'], $rows);
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * {@inheritDoc}
     * @return mixed
     * @see SelectTrait::first
     */
    public function first()
    {
        try {
            $row = $this->baseQuery->first();

            if ($row === null) {
                return $row;
            } else {
                return $this->processFetchedRow($row);
            }
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * {@inheritDoc}
     * @see SelectTrait::chunk
     */
    public function chunk(int $size, callable $callback)
    {
        try {
            return $this->baseQuery->chunk($size, function (array $rows) use ($callback) {
                $callback(array_map([$this, 'processFetchedRow'], $rows));
            });
        } catch (\Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Processes a row fetched from the database before returning it.
     *
     * @param array $row
     * @return mixed
     */
    protected function processFetchedRow(array $row)
    {
        return $row;
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
