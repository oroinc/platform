<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log\Processor\Stub;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use ProxyManager\Proxy\ValueHolderInterface;

class MessageProcessorProxy implements MessageProcessorInterface, ValueHolderInterface
{
    /** @var MessageProcessorInterface */
    private $messageProcessor;

    /**
     * @param MessageProcessorInterface $messageProcessor
     */
    public function __construct(MessageProcessorInterface $messageProcessor)
    {
        $this->messageProcessor = $messageProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getWrappedValueHolderValue()
    {
        return $this->messageProcessor;
    }
}
