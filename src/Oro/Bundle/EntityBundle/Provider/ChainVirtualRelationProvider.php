<?php

namespace Oro\Bundle\EntityBundle\Provider;

class ChainVirtualRelationProvider extends AbstractChainProvider implements VirtualRelationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isVirtualRelation($className, $fieldName)
    {
        /** @var VirtualRelationProviderInterface[] $providers */
        $providers = $this->getProviders();
        foreach ($providers as $provider) {
            if ($provider->isVirtualRelation($className, $fieldName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelationQuery($className, $fieldName)
    {
        return $this->findProvider($className, $fieldName)->getVirtualRelationQuery($className, $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelations($className)
    {
        /** @var VirtualRelationProviderInterface[] $providers */
        $providers = $this->getProviders();
        $result = [];

        foreach ($providers as $provider) {
            $virtualRelations = $provider->getVirtualRelations($className);
            if (!empty($virtualRelations)) {
                $result = array_merge($result, $virtualRelations);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetJoinAlias($className, $fieldName, $selectFieldName = null)
    {
        return $this->findProvider($className, $fieldName)->getTargetJoinAlias(
            $className,
            $fieldName,
            $selectFieldName
        );
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return VirtualRelationProviderInterface
     */
    protected function findProvider($className, $fieldName)
    {
        $foundProvider = null;
        /** @var VirtualRelationProviderInterface[] $providers */
        $providers = $this->getProviders();
        foreach ($providers as $provider) {
            if ($provider->isVirtualRelation($className, $fieldName)) {
                $foundProvider = $provider;
                break;
            }
        }

        if ($foundProvider === null) {
            throw new \RuntimeException(
                sprintf(
                    'A query for relation "%s" in class "%s" was not found.',
                    $fieldName,
                    $className
                )
            );
        }

        return $foundProvider;
    }
}
