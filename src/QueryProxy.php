<?php

namespace Finesse\MiniDB;

use Finesse\QueryScribe\QueryProxy as BaseQueryProxy;

/**
 * Helps to extend a query object dynamically.
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
     * @return mixed[]
     */
    public function get(): array
    {
        try {
            $rows = $this->baseQuery->get();
        } catch (\Throwable $exception) {
            return $this->handleBaseQueryException($exception);
        }

        return array_map([$this, 'processFetchedRow'], $rows);
    }

    /**
     * {@inheritDoc}
     * @return mixed
     */
    public function first()
    {
        try {
            $row = $this->baseQuery->first();
        } catch (\Throwable $exception) {
            return $this->handleBaseQueryException($exception);
        }

        if ($row === null) {
            return $row;
        } else {
            return $this->processFetchedRow($row);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function chunk(int $size, callable $callback)
    {
        try {
            return $this->baseQuery->chunk($size, function (array $rows) use ($callback) {
                $callback(array_map([$this, 'processFetchedRow'], $rows));
            });
        } catch (\Throwable $exception) {
            return $this->handleBaseQueryException($exception);
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
}
