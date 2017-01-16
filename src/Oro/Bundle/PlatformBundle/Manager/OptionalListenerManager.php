<?php

namespace Oro\Bundle\PlatformBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;

class OptionalListenerManager
{
    /**
     * @var array
     */
    protected $optionalListeners = [];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param array $optionalListeners
     * @param ContainerInterface $container
     */
    public function __construct(array $optionalListeners, ContainerInterface $container)
    {
        $this->optionalListeners = $optionalListeners;
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getListeners()
    {
        return $this->optionalListeners;
    }

    /**
     * Set one listener as disabled
     *
     * @param string $listenerId
     *
     * @throws \InvalidArgumentException When given listener doesn't exist
     */
    public function disableListener($listenerId)
    {
        if (in_array($listenerId, $this->optionalListeners)) {
            $this->container->get($listenerId)->setEnabled(false);
        } else {
            throw new \InvalidArgumentException(
                sprintf('Listener "%s" does not exist or not optional', $listenerId)
            );
        }
    }

    /**
     * Disable specified listeners
     *
     * @param array $listeners
     */
    public function disableListeners(array $listeners)
    {
        foreach ($listeners as $listener) {
            $this->disableListener($listener);
        }
    }

    /**
     * Set one listener as enabled
     *
     * @param string $listenerId
     *
     * @throws \InvalidArgumentException When given listener doesn't exist
     */
    public function enableListener($listenerId)
    {
        if (in_array($listenerId, $this->optionalListeners)) {
            $this->container->get($listenerId)->setEnabled(true);
        } else {
            throw new \InvalidArgumentException(
                sprintf('Listener "%s" does not exist or not optional', $listenerId)
            );
        }
    }

    /**
     * Enable specified listeners
     *
     * @param array $listeners
     */
    public function enableListeners(array $listeners)
    {
        foreach ($listeners as $listener) {
            $this->enableListener($listener);
        }
    }
}
