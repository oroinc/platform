<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks whether the container has loggers with handlers that need to be cleared after each processor,
 * and if so, removes all messages from these handlers.
 */
class ClearLoggerExtension extends AbstractExtension
{
    /** @var ContainerInterface */
    private $container;

    /** @var string[] */
    private $persistentLoggers;

    /**
     * @param ContainerInterface $container
     * @param string[]           $persistentLoggers
     */
    public function __construct(ContainerInterface $container, array $persistentLoggers)
    {
        $this->container = $container;
        $this->persistentLoggers = $persistentLoggers;
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $this->clear();
    }

    /**
     *{@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        $this->clear();
    }

    private function clear()
    {
        foreach ($this->persistentLoggers as $serviceId) {
            if ($this->container->initialized($serviceId)) {
                $logger = $this->container->get($serviceId);
                if ($logger instanceof Logger) {
                    $this->clearLogger($logger);
                }
            }
        }
    }

    private function clearLogger(Logger $logger)
    {
        $handlers = $logger->getHandlers();
        foreach ($handlers as $handler) {
            $this->clearHandler($handler);
        }
        $logger->reset();
    }

    private function clearHandler(HandlerInterface $handler)
    {
        if (method_exists($handler, 'clear')) {
            // do clear because each processor is a separate "request" for the consumer
            // and the logging should starts from the scratch for each processor
            $handler->clear();
        }
    }
}
