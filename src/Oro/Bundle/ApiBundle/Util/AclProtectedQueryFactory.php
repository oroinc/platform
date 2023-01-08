<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\EntitySerializer\DoctrineHelper as SerializerDoctrineHelper;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\QueryFactory;
use Oro\Component\EntitySerializer\QueryResolver;

/**
 * This query factory modifies API queries in order to protect data
 * that can be retrieved via these queries.
 */
class AclProtectedQueryFactory extends QueryFactory
{
    private QueryModifierRegistry $queryModifier;
    private ?RequestType $requestType = null;

    public function __construct(
        SerializerDoctrineHelper $doctrineHelper,
        QueryResolver $queryResolver,
        QueryModifierRegistry $queryModifier
    ) {
        parent::__construct($doctrineHelper, $queryResolver);
        $this->queryModifier = $queryModifier;
    }

    public function getRequestType(): ?RequestType
    {
        return $this->requestType;
    }

    public function setRequestType(?RequestType $requestType): void
    {
        $this->requestType = $requestType;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuery(QueryBuilder $qb, EntityConfig $config): Query
    {
        if (null === $this->requestType) {
            throw new \LogicException('The query factory was not initialized.');
        }

        // ensure that FROM clause is initialized
        $qb->getRootAliases();
        // do query modification
        $this->queryModifier->modifyQuery(
            $qb,
            (bool)$config->get(AclProtectedQueryResolver::SKIP_ACL_FOR_ROOT_ENTITY),
            $this->requestType
        );

        return parent::getQuery($qb, $config);
    }
}
