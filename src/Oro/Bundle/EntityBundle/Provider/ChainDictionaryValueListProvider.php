<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder;

class ChainDictionaryValueListProvider
{
    /** @var array */
    private $providers;

    /** @var DictionaryValueListProviderInterface[] */
    private $sorted;

    /**
     * Registers the provider in the chain.
     *
     * @param DictionaryValueListProviderInterface $provider
     * @param int                                  $priority
     */
    public function addProvider(DictionaryValueListProviderInterface $provider, $priority = 0)
    {
        $this->providers[$priority][] = $provider;
        $this->sorted                 = null;
    }

    /**
     * Returns the registered providers sorted by priority.
     *
     * @return DictionaryValueListProviderInterface[]
     */
    protected function getProviders()
    {
        if (null === $this->sorted) {
            if (empty($this->providers)) {
                $this->sorted = [];
            } else {
                krsort($this->providers);
                $this->sorted = call_user_func_array('array_merge', $this->providers);
            }
        }

        return $this->sorted;
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

        foreach ($this->getProviders() as $provider) {
            if ($provider->supports($className)) {
                $serializationConfig = $provider->getSerializationConfig($className);
                if ($serializationConfig) {
                    return $serializationConfig;
                }
            }
        }

        return null;
    }

    /**
     * Gets a query builder for getting dictionary item values for a given dictionary class
     *
     * @param string $className The FQCN of a dictionary entity
     *
     * @return QueryBuilder|SqlQueryBuilder|null QueryBuilder or SqlQueryBuilder if the given entity can be processed
     *                                           NULL otherwise
     */
    public function getValueListQueryBuilder($className)
    {
        if (null === $className) {
            return null;
        }

        foreach ($this->getProviders() as $provider) {
            if ($provider->supports($className)) {
                $dictionaryItems = $provider->getValueListQueryBuilder($className);
                if ($dictionaryItems) {
                    return $dictionaryItems;
                }
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
        foreach ($this->getProviders() as $provider) {
            $supportedClasses = array_merge($supportedClasses, $provider->getSupportedEntityClasses());
        }

        return array_unique($supportedClasses);
    }
}
