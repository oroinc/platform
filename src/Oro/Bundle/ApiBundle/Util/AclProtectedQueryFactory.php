<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\QueryFactory;

/**
 * This query factory modifies Data API queries in order to protect data
 * that can be retrieved via these queries.
 */
class AclProtectedQueryFactory extends QueryFactory
{
    public const SKIP_ACL_FOR_ROOT_ENTITY = 'skip_acl_for_root_entity';

    /** @var AclHelper */
    private $aclHelper;

    /** @var QueryModifierRegistry */
    private $queryModifier;

    /** @var RequestType|null */
    private $requestType;

    /**
     * @param AclHelper $aclHelper
     */
    public function setAclHelper(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param QueryModifierRegistry $queryModifier
     */
    public function setQueryModifier(QueryModifierRegistry $queryModifier)
    {
        $this->queryModifier = $queryModifier;
    }

    /**
     * @param RequestType|null $requestType
     */
    public function setRequestType(RequestType $requestType = null)
    {
        $this->requestType = $requestType;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(QueryBuilder $qb, EntityConfig $config)
    {
        $skipRootEntity = (bool)$config->get(self::SKIP_ACL_FOR_ROOT_ENTITY);
        if (null !== $this->requestType) {
            // ensure that FROM clause is initialized
            $qb->getRootAliases();
            // do query modification
            $this->queryModifier->modifyQuery($qb, $skipRootEntity, $this->requestType);
        }
        if ($skipRootEntity) {
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
