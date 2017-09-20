<?php

namespace Oro\Component\MessageQueue\Consumption\Extension;

use Psr\Log\LoggerInterface;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class LoggerExtension extends AbstractExtension
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context)
    {
        $context->setLogger($this->logger);
        $this->logger->debug('Set logger to the context');
    }
}
