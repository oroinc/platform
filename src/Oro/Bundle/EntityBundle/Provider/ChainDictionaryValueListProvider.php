<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\QueryBuilder;

/**
 * Provides information about dictionaries.
 */
class ChainDictionaryValueListProvider
{
    /** @var iterable|DictionaryValueListProviderInterface[] */
    private iterable $providers;

    /**
     * @param iterable|DictionaryValueListProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * Checks whether the given class is a dictionary.
     */
    public function isSupportedEntityClass(string $className): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($className)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the configuration of the entity serializer for the given dictionary class.
     */
    public function getSerializationConfig(string $className): ?array
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($className)) {
                return $provider->getSerializationConfig($className);
            }
        }

        return null;
    }

    /**
     * Gets a query builder for getting dictionary item values for the given dictionary class.
     */
    public function getValueListQueryBuilder(string $className): ?QueryBuilder
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($className)) {
                return $provider->getValueListQueryBuilder($className);
            }
        }

        return null;
    }

    /**
     * Gets a list of dictionary entity classes.
     *
     * @return string[]
     */
    public function getSupportedEntityClasses(): array
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
