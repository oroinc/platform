<?php

namespace Oro\Bundle\LoggerBundle\Async\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Log\MessageProcessorClassProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Writes info about the processor executed before consumption was interrupted to the log.
 * This info might be helpful to find time consuming processors.
 * The typical use case is
 * * Run 'php bin/console oro:logger:level info "X minutes" --env=prod' (this will cause to restart all consumers;
 *   all new instances of consumers will use new log level)
 * * If some consumers was not stopped after this command, wait till they stop and find in the log which
 *   processors work before they stopped.
 */
class InterruptionDetailConsumptionExtension extends AbstractExtension
{
    /** @var ContainerInterface */
    private $container;

    /** @var MessageProcessorClassProvider */
    private $messageProcessorClassProvider;

    /** @var string|null */
    private $lastProcessorClassName;

    public function __construct(
        ContainerInterface $container,
        MessageProcessorClassProvider $messageProcessorClassProvider
    ) {
        $this->container = $container;
        $this->messageProcessorClassProvider = $messageProcessorClassProvider;
    }

    #[\Override]
    public function onPostReceived(Context $context)
    {
        // remember the current processor
        $this->lastProcessorClassName = $this->messageProcessorClassProvider->getMessageProcessorClassByName(
            $context->getMessageProcessorName()
        );
    }

    #[\Override]
    public function onIdle(Context $context)
    {
        // reset the extension state if no messages to process
        $this->lastProcessorClassName = null;
    }

    #[\Override]
    public function onInterrupted(Context $context)
    {
        if ($this->lastProcessorClassName) {
            // reset caches to make sure that log level is up-to-date
            $this->resetCaches();

            // write the processor executed just before interrupt of consuming to the log
            $context->getLogger()->info(
                sprintf(
                    'The last processor executed before interrupt of consuming was "%s"',
                    $this->lastProcessorClassName
                )
            );

            // reset the extension state
            $this->lastProcessorClassName = null;
        }
    }

    private function resetCaches(): void
    {
        if ($this->container->initialized('oro_logger.cache')) {
            $this->container->get('oro_logger.cache')->reset();
        }
        if ($this->container->initialized('oro_config.user')) {
            $this->container->get('oro_config.user')->resetMemoryCache();
        }
    }
}
