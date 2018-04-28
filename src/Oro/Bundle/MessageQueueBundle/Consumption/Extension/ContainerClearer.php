<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ResettableContainerInterface;

/**
 * Removes all services except persistent ones from the service container
 * and reset all extensions implement ResettableExtensionInterface.
 */
class ContainerClearer implements ClearerInterface, ChainExtensionAwareInterface
{
    /** @var string[] */
    private $persistentServices = [];

    /** @var ResettableContainerInterface */
    private $container;

    /** @var ExtensionInterface|null */
    private $rootChainExtension;

    /**
     * @param ResettableContainerInterface $container
     */
    public function __construct(ResettableContainerInterface $container)
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
            }
        }

        // remove all services from the container
        $this->container->reset();

        // restore persistent services in the container
        foreach ($persistentServices as $serviceId => $serviceInstance) {
            $this->container->set($serviceId, $serviceInstance);
        }
    }
}
