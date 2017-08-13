<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Processor\Stub;

use ProxyManager\Proxy\ValueHolderInterface;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

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
