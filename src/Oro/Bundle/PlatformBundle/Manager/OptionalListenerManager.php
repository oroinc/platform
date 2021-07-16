<?php

namespace Oro\Bundle\PlatformBundle\Manager;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manager to control OptionalListenerInterface execution
 */
class OptionalListenerManager
{
    /**
     * @var OptionalListenerInterface[]
     */
    protected $optionalListeners = [];

    /**
     * @var OptionalListenerInterface[]
     */
    protected $disabledListeners = [];

    /**
     * @var ContainerInterface
     */
    protected $container;

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

            $this->disabledListeners[$listenerId] = $listenerId;
        } else {
            throw new \InvalidArgumentException(
                sprintf('Listener "%s" does not exist or not optional', $listenerId)
            );
        }
    }

    /**
     * Disable specified listeners
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

            unset($this->disabledListeners[$listenerId]);
        } else {
            throw new \InvalidArgumentException(
                sprintf('Listener "%s" does not exist or not optional', $listenerId)
            );
        }
    }

    /**
     * Enable specified listeners
     */
    public function enableListeners(array $listeners)
    {
        foreach ($listeners as $listener) {
            $this->enableListener($listener);
        }
    }

    public function getDisabledListeners(): array
    {
        return array_values($this->disabledListeners);
    }
}
