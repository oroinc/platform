<?php

namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Psr\Log\LoggerInterface;

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
