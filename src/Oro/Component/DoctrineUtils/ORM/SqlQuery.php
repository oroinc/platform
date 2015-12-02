<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\AbstractQuery;

class SqlQuery extends AbstractQuery
{
    /** @var DbalQueryBuilder */
    protected $qb;

    /**
     * @param DbalQueryBuilder $qb
     */
    public function setQueryBuilder(DbalQueryBuilder $qb)
    {
        $this->qb = $qb;
    }

    /**
     * Returns the query builder used for build this query.
     *
     * @return DbalQueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQL()
    {
        return $this->qb->getSQL();
    }

    /**
     * {@inheritdoc}
     */
    // @codingStandardsIgnoreStart
    protected function _doExecute()
    {
        return $this->qb->execute();
    }
    // @codingStandardsIgnoreEnd

    /**
     * Deep clone of all expression objects in the SQL parts.
     *
     * @return void
     */
    public function __clone()
    {
        parent::__clone();
        $this->qb = clone $this->qb;
    }
}
