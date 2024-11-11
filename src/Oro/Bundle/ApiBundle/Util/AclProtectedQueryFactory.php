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
    private ?array $options = null;

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

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(?array $options): void
    {
        $this->options = $options;
    }

    #[\Override]
    public function getQuery(QueryBuilder $qb, EntityConfig $config): Query
    {
        if (null === $this->requestType) {
            throw new \LogicException('The query factory was not initialized.');
        }

        // ensure that FROM clause is initialized
        $qb->getRootAliases();

        // do query modification
        $previousConfigValues = $this->updateConfig($config);
        try {
            $this->modifyQuery($qb, $config);

            return parent::getQuery($qb, $config);
        } finally {
            $this->restoreConfig($config, $previousConfigValues);
        }
    }

    private function modifyQuery(QueryBuilder $qb, EntityConfig $config): void
    {
        $options = [];
        $resourceClass = $config->get(ConfigUtil::RESOURCE_CLASS);
        if ($resourceClass) {
            $options['resourceClass'] = $resourceClass;
        }
        $this->queryModifier->modifyQuery(
            $qb,
            (bool)$config->get(AclProtectedQueryResolver::SKIP_ACL_FOR_ROOT_ENTITY),
            $this->requestType,
            $options
        );
    }

    private function updateConfig(EntityConfig $config): array
    {
        $previousConfigValues = [];
        if ($this->options) {
            foreach ($this->options as $key => $value) {
                $previousConfigValues[$key] = $config->has($key)
                    ? ['v' => $config->get($key)]
                    : null;
                $config->set($key, $value);
            }
        }

        return $previousConfigValues;
    }

    private function restoreConfig(EntityConfig $config, array $previousConfigValues): void
    {
        foreach ($previousConfigValues as $key => $value) {
            if (null === $value) {
                $config->remove($key);
            } else {
                $config->set($key, $value['v']);
            }
        }
    }
}
