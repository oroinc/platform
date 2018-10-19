<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks whether the container has loggers with handlers that need to be cleared after each processor,
 * and if so, removes all mesages from these handlers.
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
        foreach ($this->persistentLoggers as $serviceId) {
            if ($this->container->initialized($serviceId)) {
                $logger = $this->container->get($serviceId);
                if ($logger instanceof Logger) {
                    $this->clearLogger($logger);
                }
            }
        }
    }

    /**
     * @param Logger $logger
     */
    private function clearLogger(Logger $logger)
    {
        $handlers = $logger->getHandlers();
        foreach ($handlers as $handler) {
            $this->clearHandler($handler);
        }
    }

    /**
     * @param HandlerInterface $handler
     */
    private function clearHandler(HandlerInterface $handler)
    {
        if ($handler instanceof FingersCrossedHandler) {
            // do clear because each processor is a separate "request" for the consumer
            // and the logging should starts from the scratch for each processor
            $handler->clear();
        } elseif ($handler instanceof TestHandler) {
            // it is safe to clear this handler because it is not used in "prod" mode
            $handler->clear();
        }
    }
}
