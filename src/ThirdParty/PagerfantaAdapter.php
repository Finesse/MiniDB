<?php

namespace Finesse\MiniDB\ThirdParty;

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
     */
    public function getNbResults()
    {
        return $this->query->count();
    }

    /**
     * {@inheritDoc}
     */
    public function getSlice($offset, $length)
    {
        return $this->query->offset($offset)->limit($length)->get();
    }
}
