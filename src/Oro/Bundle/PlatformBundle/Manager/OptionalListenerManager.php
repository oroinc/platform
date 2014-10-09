<?php

namespace Oro\Bundle\PlatformBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;

class OptionalListenerManager
{
    /**
     * @var array
     */
    protected $optionalListeners;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param array $optionalListeners
     */
    public function __construct($optionalListeners, ContainerInterface $container)
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
     */
    public function disableListener($listenerId)
    {
        if (in_array($listenerId, $this->optionalListeners)) {
            $this->container->get($listenerId)->setEnabled(false);
        }
    }

    /**
     * Disable array of listeners ore all listeners
     *
     * @param array $listeners
     */
    public function disableListeners($listeners = [])
    {
        if (empty($listeners)) {
            $listeners = $this->optionalListeners;
        }

        foreach ($listeners as $listener) {
            $this->disableListener($listener);
        }
    }
}
