<?php

namespace Oro\Bundle\SoapBundle\Provider;

use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;
use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;

class ChainMetadataProvider implements MetadataProviderInterface
{
    /** @var MetadataProviderInterface[] */
    protected $providers;

    /**
     * @param MetadataProviderInterface[] $providers
     */
    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    /**
     * Adds all providers that marked by tag: oro_soap.metadata_provider
     *
     * @param MetadataProviderInterface $provider
     */
    public function addProvider(MetadataProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @param FormAwareInterface|EntityManagerAwareInterface $object
     *
     * @return array
     */
    public function getMetadataFor($object)
    {
        $metadata = [];

        foreach ($this->providers as $provider) {
            $metadata = array_merge($metadata, $provider->getMetadataFor($object));
        }

        return $metadata;
    }
}
