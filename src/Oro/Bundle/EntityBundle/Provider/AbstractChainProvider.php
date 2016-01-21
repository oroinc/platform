<?php

namespace Oro\Bundle\EntityBundle\Provider;

abstract class AbstractChainProvider
{
    /**
     * @var array[]
     */
    protected $providers = [];

    /**
     * @var array|null
     */
    protected $sorted;

    /**
     * Registers the given provider in the chain.
     *
     * @param mixed $provider
     * @param int $priority
     */
    public function addProvider($provider, $priority = 0)
    {
        $this->providers[$priority][] = $provider;
        $this->sorted = null;
    }

    /**
     * Sorts the internal list of providers by priority.
     *
     * @return array
     */
    protected function getProviders()
    {
        if (null === $this->sorted) {
            ksort($this->providers);

            $this->sorted = $this->providers
                ? call_user_func_array('array_merge', $this->providers)
                : [];
        }

        return $this->sorted;
    }
}
