<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\QueryBuilder;

/**
 * Provides information about dictionaries.
 */
class ChainDictionaryValueListProvider
{
    /** @var iterable|DictionaryValueListProviderInterface[] */
    private $providers;

    /**
     * @param iterable|DictionaryValueListProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * Returns the configuration of the entity serializer for a given dictionary class
     *
     * @param string $className The FQCN of a dictionary entity
     *
     * @return array|null
     */
    public function getSerializationConfig($className)
    {
        if (null === $className) {
            return null;
        }

        foreach ($this->providers as $provider) {
            if ($provider->supports($className)) {
                return $provider->getSerializationConfig($className);
            }
        }

        return null;
    }

    /**
     * Gets a query builder for getting dictionary item values for a given dictionary class
     *
     * @param string $className The FQCN of a dictionary entity
     *
     * @return QueryBuilder|null
     */
    public function getValueListQueryBuilder($className)
    {
        if (null === $className) {
            return null;
        }

        foreach ($this->providers as $provider) {
            if ($provider->supports($className)) {
                return $provider->getValueListQueryBuilder($className);
            }
        }

        return null;
    }

    /**
     * Gets a list of supported entity classes
     *
     * @return string[]
     */
    public function getSupportedEntityClasses()
    {
        $supportedClasses = [];
        foreach ($this->providers as $provider) {
            $classes = $provider->getSupportedEntityClasses();
            if ($classes) {
                $supportedClasses[] = $classes;
            }
        }
        if ($supportedClasses) {
            $supportedClasses = array_unique(array_merge(...$supportedClasses));
        }

        return $supportedClasses;
    }
}
