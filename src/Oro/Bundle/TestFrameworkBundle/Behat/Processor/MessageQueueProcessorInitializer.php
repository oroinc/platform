<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Processor;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;

/**
 * Inject message queue processor to contexts
 */
class MessageQueueProcessorInitializer implements ContextInitializer, MessageQueueProcessorAwareInterface
{
    use MessageQueueProcessorAwareTrait;

    public function initializeContext(Context $context)
    {
        if ($context instanceof MessageQueueProcessorAwareInterface) {
            $context->setMessageQueueProcessor($this->messageQueueProcessor);
        }
    }
}
