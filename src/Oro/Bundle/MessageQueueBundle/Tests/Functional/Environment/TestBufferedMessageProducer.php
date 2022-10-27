<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Environment;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;

/**
 * This class is used only in functional tests and allows to:
 * * disable/enable the buffering of messages
 * * stop/restore sending of messages
 */
class TestBufferedMessageProducer extends BufferedMessageProducer
{
    /** @var bool */
    private $enabled = true;

    /** @var bool */
    private $stopped = false;

    /**
     * Allows to enable the buffering of messages.
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disallows to enable the buffering of messages.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Indicates whether the sending of messages is stopped or not.
     */
    public function isSendingOfMessagesStopped(): bool
    {
        return $this->stopped;
    }

    /**
     * Restores sending of messages.
     */
    public function restoreSendingOfMessages(): void
    {
        $this->stopped = false;
    }

    /**
     * Stops sending of messages.
     */
    public function stopSendingOfMessages(): void
    {
        $this->stopped = true;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        if ($this->stopped) {
            return;
        }
        if (!$this->enabled && $this->isBufferingEnabled()) {
            $nestingLevel = 0;
            while ($this->isBufferingEnabled()) {
                $this->disableBuffering();
                $nestingLevel++;
            }
            try {
                parent::send($topic, $message);
            } finally {
                while ($nestingLevel > 0) {
                    $this->enableBuffering();
                    $nestingLevel--;
                }
            }
        } else {
            parent::send($topic, $message);
        }
    }
}
