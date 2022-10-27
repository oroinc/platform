<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Removes all services except persistent ones from the service container
 * and reset all extensions implement ResettableExtensionInterface.
 */
class ContainerClearer implements ClearerInterface, ChainExtensionAwareInterface
{
    /** @var string[] */
    private $persistentServices = [];

    /** @var Container */
    private $container;

    /** @var ExtensionInterface|null */
    private $rootChainExtension;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Adds the services that should not be removed during the container reset.
     * The given services are added in addition to already added services.
     *
     * @param string[] $persistentServices
     */
    public function setPersistentServices(array $persistentServices)
    {
        $this->persistentServices = array_merge(
            $this->persistentServices,
            $persistentServices
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setChainExtension(ExtensionInterface $chainExtension)
    {
        $this->rootChainExtension = $chainExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(LoggerInterface $logger)
    {
        $logger->info('Reset the container');

        // reset state of non-persistent extensions
        if ($this->rootChainExtension instanceof ResettableExtensionInterface) {
            $this->rootChainExtension->reset();
        }

        // save persistent services
        $persistentServices = [];
        foreach ($this->persistentServices as $serviceId) {
            if ($this->container->initialized($serviceId)) {
                $persistentServices[$serviceId] = $this->container->get($serviceId);

                /**
                 * Resets the state of the persistent container, to prevent the issue
                 * with persistent service re-initializing. It occurs because
                 * logic in container service emptied internal services before do reset the state of these services.
                 */
                if ($persistentServices[$serviceId] instanceof ResetInterface) {
                    $persistentServices[$serviceId]->reset();
                }
            }
        }

        // remove all services from the container
        $this->container->reset();

        // restore persistent services in the container
        $initializedPersistentServices = [];
        foreach ($persistentServices as $serviceId => $serviceInstance) {
            if ($this->container->initialized($serviceId)) {
                $initializedPersistentServices[] = $serviceId;
            } else {
                $this->container->set($serviceId, $serviceInstance);
            }
        }

        if ($initializedPersistentServices) {
            $logger->notice(
                'Next persistent services were already initialized during restoring: '
                . implode(', ', $initializedPersistentServices)
            );
        }
    }
}
