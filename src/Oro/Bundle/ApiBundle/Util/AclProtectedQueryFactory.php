<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\QueryFactory;

class AclProtectedQueryFactory extends QueryFactory
{
    const SKIP_ACL_FOR_ROOT_ENTITY = 'skip_acl_for_root_entity';

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
        if ($config->get(self::SKIP_ACL_FOR_ROOT_ENTITY)) {
            $this->aclHelper->setCheckRootEntity(false);
            try {
                $query = $this->aclHelper->apply($qb);
            } finally {
                $this->aclHelper->setCheckRootEntity(true);
            }
        } else {
            $query = $this->aclHelper->apply($qb);
        }
        $this->queryHintResolver->resolveHints($query, $config->getHints());

        return $query;
    }
}
