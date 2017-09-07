<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\IntrospectableContainerInterface;
use Symfony\Component\DependencyInjection\ResettableContainerInterface;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * This extension resets the container state between messages.
 *
 * The "persistent_services" and "persistent_processors" options can be used to configure
 * the list of services and the list of MQ processors that should not be removed during the container reset.
 * For details see "Resources/doc/container_in_consumer.md".
 */
class ContainerResetExtension extends AbstractExtension
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
     *
     * @param string[] $persistentServices
     */
    public function setPersistentServices(array $persistentServices)
    {
        $this->persistentServices = $persistentServices;
    }

    /**
     * Sets the processors that can work without the container reset.
     *
     * @param string[] $persistentProcessors
     */
    public function setPersistentProcessors(array $persistentProcessors)
    {
        $this->persistentProcessors = array_fill_keys($persistentProcessors, true);
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

        // save the persistent services
        $persistentServices = [];
        foreach ($this->persistentServices as $serviceId) {
            if ($this->container->initialized($serviceId)) {
                $persistentServices[$serviceId] = $this->container->get($serviceId);
            }
        }

        $this->container->reset();

        // restore the persistent services in the container
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
