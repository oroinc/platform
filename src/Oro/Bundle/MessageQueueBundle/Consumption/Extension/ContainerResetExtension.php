<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\IntrospectableContainerInterface;
use Symfony\Component\DependencyInjection\ResettableContainerInterface;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;

/**
 * This extension resets the container state between messages.
 *
 * The "persistent_services" and "persistent_processors" options can be used to configure
 * the list of services and the list of MQ processors that should not be removed during the container reset.
 * Also other extensions can be marked as "persistent" if they should not be recreated during the container reset.
 * For details see "Resources/doc/container_in_consumer.md".
 */
class ContainerResetExtension extends AbstractExtension implements ChainExtensionAwareInterface
{
    /**
     * The services that should not be removed during the container reset.
     *
     * @var string[]
     */
    private $persistentServices = [];

    /**
     * The processors that can work without the container reset.
     *
     * @var array [processor name => TRUE, ...]
     */
    private $persistentProcessors = [];

    /** @var ContainerInterface|ResettableContainerInterface|IntrospectableContainerInterface|null */
    private $container;

    /** @var ExtensionInterface|null */
    private $rootChainExtension;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        if ($container instanceof ResettableContainerInterface
            && $container instanceof IntrospectableContainerInterface
        ) {
            $this->container = $container;
        }
    }

    /**
     * Sets the services that should not be removed during the container reset.
     * The given services are added in addition to already set services.
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
     * Sets the processors that can work without the container reset.
     * The given processors are added in addition to already set processors.
     *
     * @param string[] $persistentProcessors
     */
    public function setPersistentProcessors(array $persistentProcessors)
    {
        $this->persistentProcessors = array_merge(
            $this->persistentProcessors,
            array_fill_keys($persistentProcessors, true)
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
    public function onPreReceived(Context $context)
    {
        if (null === $this->container) {
            return;
        }

        if (isset($this->persistentProcessors[$this->getProcessorName($context)])) {
            return;
        }

        $context->getLogger()->info('Reset the container');

        // reset state of not persistent extensions
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
        // clear the memory and prevent segmentation fault that might sometimes occur in "unserialize" function
        gc_collect_cycles();

        // restore persistent services in the container
        foreach ($persistentServices as $serviceId => $serviceInstance) {
            $this->container->set($serviceId, $serviceInstance);
        }
    }

    /**
     * @param Context $context
     *
     * @return string
     */
    private function getProcessorName(Context $context)
    {
        return $context->getMessage()->getProperty(Config::PARAMETER_PROCESSOR_NAME);
    }
}
