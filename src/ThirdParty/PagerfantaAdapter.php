<?php

namespace Finesse\MiniDB\ThirdParty;

use Finesse\MiniDB\Exceptions\DatabaseException;
use Finesse\MiniDB\Exceptions\IncorrectQueryException;
use Finesse\MiniDB\Query;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * Pagerfanta adapter
 *
 * @see https://github.com/whiteoctober/Pagerfanta Pagerfanta
 * @author Surgie
 */
class PagerfantaAdapter implements AdapterInterface
{
    /**
     * @var Query A query from which the results should be taken
     */
    protected $query;

    /**
     * @param Query $query A query from which the results should be taken. Warning, it will be modified.
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritDoc}
     * @throws DatabaseException
     * @throws IncorrectQueryException
     */
    public function getNbResults()
    {
        return $this->query->count();
    }

    /**
     * {@inheritDoc}
     * @return array[]
     * @throws DatabaseException
     * @throws IncorrectQueryException
     */
    public function getSlice($offset, $length)
    {
        return $this->query->offset($offset)->limit($length)->get();
    }
}
