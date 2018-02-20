<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\QueryCountCalculator;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;

class MassDeleteLimiter
{
    const NO_LIMIT                 = 1;
    const LIMIT_ACCESS             = 2;
    const LIMIT_MAX_RECORDS        = 3;
    const LIMIT_ACCESS_MAX_RECORDS = 4;
    const MAX_DELETE_RECORDS       = 5000;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * MassDeleteLimiter constructor.
     *
     * @param AclHelper $helper
     */
    public function __construct(AclHelper $helper)
    {
        $this->aclHelper = $helper;
    }

    /**
     * Returns limitation code from MassDeleteLimitResult parameters.
     *
     * @param MassDeleteLimitResult $result
     *
     * @return int
     */
    public function getLimitationCode(MassDeleteLimitResult $result)
    {
        $selected  = $result->getSelected();
        $deletable = $result->getDeletable();
        $maxLimit  = $result->getMaxLimit();

        if ($deletable <= $maxLimit) {
            return $selected === $deletable
                ? self::NO_LIMIT
                : self::LIMIT_ACCESS;
        } else {
            return $selected === $deletable
                ? self::LIMIT_MAX_RECORDS
                : self::LIMIT_ACCESS_MAX_RECORDS;
        }
    }

    /**
     * Limits query for deletion with access and/or performance restrictions.
     *
     * @param MassDeleteLimitResult $result
     * @param MassActionHandlerArgs $args
     */
    public function limitQuery(MassDeleteLimitResult $result, MassActionHandlerArgs $args)
    {
        $code         = $this->getLimitationCode($result);
        $queryBuilder = $args->getResults()->getSource();
        if (in_array($code, [self::LIMIT_ACCESS, self::LIMIT_ACCESS_MAX_RECORDS])) {
            $this->aclHelper->apply($queryBuilder, 'DELETE');
        }
        if (in_array($code, [self::LIMIT_MAX_RECORDS, self::LIMIT_ACCESS_MAX_RECORDS])) {
            $queryBuilder->setMaxResults($result->getMaxLimit());
        }
    }

    /**
     * @param MassActionHandlerArgs $args
     *
     * @return MassDeleteLimitResult
     */
    public function getLimitResult(MassActionHandlerArgs $args)
    {
        $query = $args->getResults()->getSource();
        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }
        $queryForDelete = $this->aclHelper->apply($this->cloneQuery($query), 'DELETE');

        return new MassDeleteLimitResult(
            QueryCountCalculator::calculateCount($query),
            QueryCountCalculator::calculateCount($queryForDelete)
        );
    }

    /**
     * Makes full clone of the given query, including its parameters and hints
     *
     * @param Query|QueryBuilder $query
     *
     * @return Query|QueryBuilder
     */
    protected function cloneQuery($query)
    {
        if ($query instanceof Query) {
            return QueryUtil::cloneQuery($query);
        }

        return clone $query;
    }
}
