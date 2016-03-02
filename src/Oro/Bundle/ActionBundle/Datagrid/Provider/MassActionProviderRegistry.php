<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Provider;

class MassActionProviderRegistry
{
    /** @var array|MassActionProviderInterface[] */
    protected $providers = [];

    /**
     * @param string $name
     * @param MassActionProviderInterface $provider
     */
    public function addProvider($name, MassActionProviderInterface $provider)
    {
        $this->providers[$name] = $provider;
    }

    /**
     * @param string $name
     * @return null|MassActionProviderInterface
     */
    public function getProvider($name)
    {
        return array_key_exists($name, $this->providers) ? $this->providers[$name] : null;
    }
}
