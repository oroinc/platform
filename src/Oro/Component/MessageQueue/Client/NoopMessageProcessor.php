<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\MessageProcessorNotSpecifiedException;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Last call message processor that is used to process messages which are not claimed by any message processor.
 */
class NoopMessageProcessor implements MessageProcessorInterface
{
    public const THROW_EXCEPTION = 'throw_exception';

    private string $defaultStatus;

    public function __construct(string $defaultStatus = MessageProcessorInterface::REQUEUE)
    {
        $this->defaultStatus = $defaultStatus;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        if ($this->defaultStatus === self::THROW_EXCEPTION) {
            throw MessageProcessorNotSpecifiedException::create();
        }

        return $this->defaultStatus;
    }
}
