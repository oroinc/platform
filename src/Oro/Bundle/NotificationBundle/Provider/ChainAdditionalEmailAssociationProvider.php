<?php

namespace Oro\Bundle\NotificationBundle\Provider;

/**
 * Represents a chain of providers for additional email associations.
 */
class ChainAdditionalEmailAssociationProvider
{
    /** @var iterable|AdditionalEmailAssociationProviderInterface[] */
    private $providers;

    /**
     * @param iterable|AdditionalEmailAssociationProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * Gets definitions of additional associations.
     *
     * @param string $entityClass
     *
     * @return array [association name => [translated label, target class], ...]
     */
    public function getAssociations(string $entityClass): array
    {
        $collectedFields = [];
        foreach ($this->providers as $provider) {
            $fields = $provider->getAssociations($entityClass);
            foreach ($fields as $fieldName => $fieldConfig) {
                if (empty($collectedFields[$fieldName])) {
                    $collectedFields[$fieldName] = $fieldConfig;
                }
            }
        }

        return $collectedFields;
    }

    /**
     * Gets a value of the given association from the given entity.
     *
     * @param object $entity
     * @param string $associationName
     *
     * @return mixed
     *
     * @throws \RuntimeException if no one provider can get the association value
     */
    public function getAssociationValue($entity, string $associationName)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isAssociationSupported($entity, $associationName)) {
                return $provider->getAssociationValue($entity, $associationName);
            }
        }

        throw new \RuntimeException('There is no provider to get the value.');
    }
}
