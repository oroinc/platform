<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Delegates building of virtual relations to child providers.
 */
class ChainVirtualRelationProvider implements VirtualRelationProviderInterface
{
    /** @var iterable|VirtualRelationProviderInterface[] */
    private $providers;

    /** @var ConfigProvider  */
    private $configProvider;

    /**
     * @param iterable|VirtualRelationProviderInterface[] $providers
     * @param ConfigProvider                              $configProvider
     */
    public function __construct(iterable $providers, ConfigProvider $configProvider)
    {
        $this->providers = $providers;
        $this->configProvider = $configProvider;
    }

    #[\Override]
    public function isVirtualRelation($className, $fieldName)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isVirtualRelation($className, $fieldName)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function getVirtualRelationQuery($className, $fieldName)
    {
        return $this->findProvider($className, $fieldName)->getVirtualRelationQuery($className, $fieldName);
    }

    #[\Override]
    public function getVirtualRelations($className)
    {
        if (!$this->isEntityAccessible($className)) {
            return [];
        }

        $relations = [];
        foreach ($this->providers as $provider) {
            $virtualRelations = $provider->getVirtualRelations($className);
            if (!empty($virtualRelations)) {
                $relations[] = $virtualRelations;
            }
        }
        if ($relations) {
            $relations = array_merge(...$relations);
        }

        return $relations;
    }

    #[\Override]
    public function getTargetJoinAlias($className, $fieldName, $selectFieldName = null)
    {
        return $this->findProvider($className, $fieldName)
            ->getTargetJoinAlias($className, $fieldName, $selectFieldName);
    }

    private function findProvider(string $className, string $fieldName): VirtualRelationProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->isVirtualRelation($className, $fieldName)) {
                return $provider;
            }
        }

        throw new \RuntimeException(sprintf(
            'A query for relation "%s" in class "%s" was not found.',
            $fieldName,
            $className
        ));
    }

    private function isEntityAccessible(string $className): bool
    {
        return
            !$this->configProvider->hasConfig($className)
            || ExtendHelper::isEntityAccessible($this->configProvider->getConfig($className));
    }
}
