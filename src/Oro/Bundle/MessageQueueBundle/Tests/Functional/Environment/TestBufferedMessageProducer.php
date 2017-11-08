<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Environment;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;

/**
 * This class is used only in functional test and by default disabled the buffering of messages at all,
 * because functional tests are be wraped with external DBAL transaction
 * due to dbIsolation and dbIsolationPerTest anotations.
 */
class TestBufferedMessageProducer extends BufferedMessageProducer
{
    /** @var bool */
    private $enabled = false;

    /**
     * Allows to enable the buffering of messages.
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * Disallows to enable the buffering of messages.
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        if (!$this->enabled && $this->isBufferingEnabled()) {
            $this->disableBuffering();
            try {
                parent::send($topic, $message);
            } finally {
                $this->enableBuffering();
            }
        } else {
            parent::send($topic, $message);
        }
    }
}
