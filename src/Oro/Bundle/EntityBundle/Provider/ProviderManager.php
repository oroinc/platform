<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\HttpFoundation\ParameterBag;

class ProviderManager
{
    protected $providers;

    public function __construct()
    {
        $this->providers = new ArrayCollection();
    }

    /**
     * @param AbstractProvider $provider
     */
    public function addProvider(AbstractProvider $provider)
    {
        $this->providers->add($provider);
    }

    /**
     * @param string       $entityName
     * @param ParameterBag $parameters
     *
     * @return AbstractProvider
     */
    public function selectByParams($entityName, ParameterBag $parameters)
    {
        $parameters->set('entityName', $entityName);

        /** @var AbstractProvider $provider */
        foreach ($this->providers as $provider) {
            if ($provider->isApplied($parameters)) {
                return $provider;
            }
        }

        return $this->providers->first();
    }
} 