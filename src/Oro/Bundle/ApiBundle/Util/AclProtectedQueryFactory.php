<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\QueryFactory;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class AclProtectedQueryFactory extends QueryFactory
{
    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param AclHelper $aclHelper
     */
    public function setAclHelper(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(QueryBuilder $qb, EntityConfig $config)
    {
        $query = $this->aclHelper->apply($qb);
        $this->queryHintResolver->resolveHints($query, $config->getHints());

        return $query;
    }
}
