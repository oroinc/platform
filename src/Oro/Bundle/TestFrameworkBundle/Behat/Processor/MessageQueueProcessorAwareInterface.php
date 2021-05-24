<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Processor;

/**
 * Message queue processor aware interface that helps inject message processor
 */
interface MessageQueueProcessorAwareInterface
{
    public function setMessageQueueProcessor(MessageQueueProcessorInterface $messageQueueProcessor): void;
}
