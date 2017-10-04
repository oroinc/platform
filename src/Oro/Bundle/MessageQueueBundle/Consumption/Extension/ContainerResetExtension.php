<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

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
    /** @var array [processor name => TRUE, ...] */
    private $persistentProcessors = [];

    /** @var ClearerInterface[] */
    private $clearers;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
    }

    /**
     * @param ClearerInterface[] $clearers
     *
     * @deprecated since 2.0. The clearers should be injected via the constructor
     */
    public function setClearers(array $clearers)
    {
        $this->clearers = $clearers;
    }

    /**
     * @param string[] $persistentServices
     *
     * @deprecated since 2.0. The persistent services should be added to ContainerClearer service
     * @see        \Oro\Bundle\MessageQueueBundle\Consumption\Extension\ContainerClearer::setPersistentServices
     */
    public function setPersistentServices(array $persistentServices)
    {
        foreach ($this->clearers as $clearer) {
            if ($clearer instanceof ContainerClearer) {
                $clearer->setPersistentServices($persistentServices);
            }
        }
    }

    /**
     * Adds the processors that can work without the container reset.
     * The given processors are added in addition to already added processors.
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
        foreach ($this->clearers as $clearer) {
            if ($clearer instanceof ChainExtensionAwareInterface) {
                $clearer->setChainExtension($chainExtension);
            }
        }
    }

    /**
     * {@inheritdoc}
     * @deprecated since 2.0. Not removed due to BC break
     */
    public function onPreReceived(Context $context)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $processorName = $context->getMessage()->getProperty(Config::PARAMETER_PROCESSOR_NAME);
        if (!isset($this->persistentProcessors[$processorName])) {
            // delegate the container reset to clearers
            $logger = $context->getLogger();
            foreach ($this->clearers as $clearer) {
                $clearer->clear($logger);
            }
        }
    }
}
