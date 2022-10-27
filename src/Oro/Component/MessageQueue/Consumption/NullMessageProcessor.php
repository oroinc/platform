<?php

namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Exception\MessageProcessorNotFoundException;
use Oro\Component\MessageQueue\Exception\MessageProcessorNotSpecifiedException;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Represents a missing message processor that must not process any message. Throws an exception when called.
 */
class NullMessageProcessor implements MessageProcessorInterface
{
    private string $missingMessageProcessorName;

    public function __construct(string $missingMessageProcessorName = '')
    {
        $this->missingMessageProcessorName = $missingMessageProcessorName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        if ($this->missingMessageProcessorName) {
            throw MessageProcessorNotFoundException::create($this->missingMessageProcessorName);
        }

        throw MessageProcessorNotSpecifiedException::create();
    }
}
