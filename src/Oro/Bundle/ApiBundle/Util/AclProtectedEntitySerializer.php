<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * This entity serializer modifies all used by it queries in order to protect data.
 */
class AclProtectedEntitySerializer extends EntitySerializer
{
    private array $contextStack = [];

    /**
     * {@inheritDoc}
     */
    public function serialize(
        QueryBuilder $qb,
        EntityConfig|array $config,
        array $context = [],
        bool $skipPostSerializationForPrimaryEntities = false
    ): array {
        $this->setContext($context);
        try {
            return parent::serialize($qb, $config, $context);
        } finally {
            $this->resetContext();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function serializeEntities(
        array $entities,
        string $entityClass,
        EntityConfig|array $config,
        array $context = [],
        bool $skipPostSerializationForPrimaryEntities = false
    ): array {
        $this->setContext($context);
        try {
            return parent::serializeEntities($entities, $entityClass, $config, $context);
        } finally {
            $this->resetContext();
        }
    }

    private function setContext(array $context): void
    {
        // push the context to the stack
        $this->contextStack[] = $context;

        // apply the context
        $this->applyContext($context);
    }

    private function resetContext(): void
    {
        // remove the last context from the stack
        array_pop($this->contextStack);

        $context = end($this->contextStack);
        if (false !== $context) {
            // apply the previous context
            $this->applyContext($context);
        } else {
            // clear the context
            $this->configConverter->setRequestType(null);
            $this->queryFactory->setRequestType(null);
            $this->fieldAccessor->setRequestType(null);
        }
    }

    private function applyContext(array $context): void
    {
        if (isset($context[Context::REQUEST_TYPE])) {
            $requestType = $context[Context::REQUEST_TYPE];
            $this->configConverter->setRequestType($requestType);
            $this->queryFactory->setRequestType($requestType);
            $this->fieldAccessor->setRequestType($requestType);
        }
    }
}
