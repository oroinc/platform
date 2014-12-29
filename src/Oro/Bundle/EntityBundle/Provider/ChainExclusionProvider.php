<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

class ChainExclusionProvider implements ExclusionProviderInterface
{
    /**
     * @var ExclusionProviderInterface[]
     */
    protected $providers = [];

    /**
     * Registers the given provider in the chain
     *
     * @param ExclusionProviderInterface $provider
     */
    public function addProvider(ExclusionProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredEntity($className)
    {
        foreach ($this->providers as $provider) {
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
        foreach ($this->providers as $provider) {
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
        foreach ($this->providers as $provider) {
            if ($provider->isIgnoredRelation($metadata, $associationName)) {
                return true;
            }
        }

        return false;
    }
}
