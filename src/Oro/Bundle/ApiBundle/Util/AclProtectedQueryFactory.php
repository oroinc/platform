<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\EntitySerializer\DoctrineHelper as SerializerDoctrineHelper;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\QueryFactory;
use Oro\Component\EntitySerializer\QueryResolver;

/**
 * This query factory modifies Data API queries in order to protect data
 * that can be retrieved via these queries.
 */
class AclProtectedQueryFactory extends QueryFactory
{
    /** @var QueryModifierRegistry */
    private $queryModifier;

    /** @var RequestType|null */
    private $requestType;

    /**
     * @param SerializerDoctrineHelper $doctrineHelper
     * @param QueryResolver            $queryResolver
     * @param QueryModifierRegistry    $queryModifier
     */
    public function __construct(
        SerializerDoctrineHelper $doctrineHelper,
        QueryResolver $queryResolver,
        QueryModifierRegistry $queryModifier
    ) {
        parent::__construct($doctrineHelper, $queryResolver);
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
        if (null !== $this->requestType) {
            // ensure that FROM clause is initialized
            $qb->getRootAliases();
            // do query modification
            $this->queryModifier->modifyQuery(
                $qb,
                (bool)$config->get(AclProtectedQueryResolver::SKIP_ACL_FOR_ROOT_ENTITY),
                $this->requestType
            );
        }

        return parent::getQuery($qb, $config);
    }
}
