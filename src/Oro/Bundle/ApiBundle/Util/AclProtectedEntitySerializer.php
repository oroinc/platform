<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * This entity serializer modifies all used by it queries in order to protect data.
 */
class AclProtectedEntitySerializer extends EntitySerializer
{
    /**
     * {@inheritdoc}
     */
    public function serialize(QueryBuilder $qb, $config, array $context = [])
    {
        if (isset($context[Context::REQUEST_TYPE])) {
            $requestType = $context[Context::REQUEST_TYPE];
            $this->queryFactory->setRequestType($requestType);
            $this->fieldAccessor->setRequestType($requestType);
            try {
                return parent::serialize($qb, $config, $context);
            } finally {
                $this->queryFactory->setRequestType();
                $this->fieldAccessor->setRequestType();
            }
        }

        return parent::serialize($qb, $config, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function serializeEntities(array $entities, $entityClass, $config, array $context = [])
    {
        if (isset($context[Context::REQUEST_TYPE])) {
            $requestType = $context[Context::REQUEST_TYPE];
            $this->queryFactory->setRequestType($requestType);
            $this->fieldAccessor->setRequestType($requestType);
            try {
                return parent::serializeEntities($entities, $entityClass, $config, $context);
            } finally {
                $this->queryFactory->setRequestType();
                $this->fieldAccessor->setRequestType();
            }
        }

        return parent::serializeEntities($entities, $entityClass, $config, $context);
    }
}
