<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\Query;
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

    #[\Override]
    public function serialize(QueryBuilder $qb, EntityConfig|array $config, array $context = []): array
    {
        $this->setContext($context);
        try {
            return parent::serialize($qb, $config, $context);
        } finally {
            $this->resetContext();
        }
    }

    #[\Override]
    public function serializeEntities(
        array $entities,
        string $entityClass,
        EntityConfig|array $config,
        array $context = []
    ): array {
        $this->setContext($context);
        try {
            return parent::serializeEntities($entities, $entityClass, $config, $context);
        } finally {
            $this->resetContext();
        }
    }

    #[\Override]
    public function buildQuery(
        QueryBuilder $qb,
        EntityConfig|array $config,
        array $context = [],
        ?callable $queryModifier = null
    ): Query {
        if (null !== $queryModifier) {
            $queryModifier = $this->getQueryModifier($queryModifier);
        }
        $this->setContext($context);
        try {
            return parent::buildQuery($qb, $config, $context, $queryModifier);
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
            $this->queryFactory->setOptions(null);
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
        $queryFactoryOptions = [];
        if ($context[AclProtectedQueryResolver::SKIP_ACL_FOR_ROOT_ENTITY] ?? false) {
            $queryFactoryOptions[AclProtectedQueryResolver::SKIP_ACL_FOR_ROOT_ENTITY] = true;
        }
        if ($queryFactoryOptions) {
            $this->queryFactory->setOptions($queryFactoryOptions);
        }
    }

    private function getQueryModifier(callable $queryModifier): callable
    {
        return function (QueryBuilder $qb, EntityConfig $entityConfig, array $context) use ($queryModifier) {
            $queryModifier($qb, $entityConfig, $context);
            (new ComputedFieldsWhereExpressionModifier())->updateQuery($qb);
        };
    }
}
