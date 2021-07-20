<?php

namespace Oro\Component\MessageQueue\Client;

/**
 * The message builder that uses a callback function to build a message.
 */
class CallbackMessageBuilder implements MessageBuilderInterface
{
    /** @var callable */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritDoc}
     */
    public function getMessage()
    {
        return \call_user_func($this->callback);
    }
}
