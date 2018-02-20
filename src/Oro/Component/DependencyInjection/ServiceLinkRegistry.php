<?php

namespace Oro\Component\DependencyInjection;

use Oro\Component\DependencyInjection\Exception\UnknownAliasException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ServiceLinkRegistry
{
    /** @var ContainerInterface */
    private $container;

    /** @var ServiceLink[] */
    private $links = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Gets service by an alias
     *
     * @param string $alias
     *
     * @return object
     */
    public function get($alias)
    {
        if (!isset($this->links[$alias])) {
            throw new UnknownAliasException($alias);
        }

        return $this->links[$alias]->getService();
    }

    /**
     * @param string $alias
     *
     * @return bool
     */
    public function has($alias)
    {
        return isset($this->links[$alias]);
    }

    /**
     * @param string $serviceId
     * @param string $alias
     */
    public function add($serviceId, $alias)
    {
        $this->links[$alias] = new ServiceLink($this->container, $serviceId);
    }
}
