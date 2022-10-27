<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Processor;

/**
 * Message queue processor aware trait that helps inject message processor
 */
trait MessageQueueProcessorAwareTrait
{
    /** @var MessageQueueProcessorInterface */
    private $messageQueueProcessor;

    public function setMessageQueueProcessor(MessageQueueProcessorInterface $messageQueueProcessor): void
    {
        $this->messageQueueProcessor = $messageQueueProcessor;
    }
}
