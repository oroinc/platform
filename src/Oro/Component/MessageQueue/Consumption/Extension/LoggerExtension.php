<?php

namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Psr\Log\LoggerInterface;

/**
 * Injects a logger instance into the message consumption context.
 *
 * This extension is responsible for setting up logging during message queue consumption.
 * It provides the logger to the consumption context at the start of the consumption process,
 * enabling all other extensions and message processors to log their activities.
 */
class LoggerExtension extends AbstractExtension
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[\Override]
    public function onStart(Context $context)
    {
        $context->setLogger($this->logger);
        $this->logger->debug('Set logger to the context');
    }
}
