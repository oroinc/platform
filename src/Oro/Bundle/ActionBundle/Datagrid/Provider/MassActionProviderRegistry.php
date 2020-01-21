<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Provider;

use Psr\Container\ContainerInterface;

/**
 * The registry of mass action providers.
 */
class MassActionProviderRegistry
{
    /** @var ContainerInterface */
    private $providers;

    /**
     * @param ContainerInterface $providers
     */
    public function __construct(ContainerInterface $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @param string $name
     *
     * @return MassActionProviderInterface|null
     */
    public function getProvider(string $name): ?MassActionProviderInterface
    {
        if (!$this->providers->has($name)) {
            return null;
        }

        return $this->providers->get($name);
    }
}
