<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datasource\Orm\DeletionIterableResult;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class MassDeleteLimiter
{
    const NO_LIMIT                 = 1;
    const LIMIT_ACCESS             = 2;
    const LIMIT_MAX_RECORDS        = 3;
    const LIMIT_ACCESS_MAX_RECORDS = 4;
    const MAX_DELETE_RECORDS       = 1000;

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
        $query              = $args->getResults()->getSource();
        $resultsForSelected = new DeletionIterableResult($query);
        $deletableQuery     = $this->cloneQuery($query);

        $accessLimitedQuery = $this->aclHelper->apply($deletableQuery, 'DELETE');
        $resultsForDelete   = new DeletionIterableResult($accessLimitedQuery);

        return new MassDeleteLimitResult($resultsForSelected->count(), $resultsForDelete->count());
    }

    /**
     * Clones $query. Also clones parameters for Doctrine\ORM\Query case.
     *
     * @param Query|QueryBuilder $query
     *
     * @return Query|QueryBuilder
     */
    protected function cloneQuery($query)
    {
        $cloned = clone $query;
        if ($query instanceof Query) {
            $cloned->setParameters(clone $query->getParameters());
        }

        return $cloned;
    }
}
