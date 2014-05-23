<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

class ChainExclusionProvider implements ExclusionProviderInterface
{
    /**
     * @var array[]
     */
    protected $providers = [];

    /**
     * @var ExclusionProviderInterface[]
     */
    protected $sorted;

    /**
     * Registers the given provider in the chain
     *
     * @param ExclusionProviderInterface $provider
     * @param integer                    $priority
     */
    public function addProvider(ExclusionProviderInterface $provider, $priority = 0)
    {
        $this->providers[$priority][] = $provider;
        $this->sorted                 = null;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredEntity($className)
    {
        $providers = $this->getProviders();
        foreach ($providers as $provider) {
            if ($provider->isIgnoredEntity($className)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        $providers = $this->getProviders();
        foreach ($providers as $provider) {
            if ($provider->isIgnoredField($metadata, $fieldName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        $providers = $this->getProviders();
        foreach ($providers as $provider) {
            if ($provider->isIgnoredRelation($metadata, $associationName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sorts the internal list of providers by priority.
     *
     * @return ExclusionProviderInterface[]
     */
    protected function getProviders()
    {
        if (null === $this->sorted) {
            krsort($this->providers);
            $this->sorted = !empty($this->providers)
                ? call_user_func_array('array_merge', $this->providers)
                : [];
        }

        return $this->sorted;
    }
}
