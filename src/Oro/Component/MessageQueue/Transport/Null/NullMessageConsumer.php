<?php
namespace Oro\Component\MessageQueue\Transport\Null;

use Oro\Component\MessageQueue\Transport\Destination;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageConsumer;

class NullMessageConsumer implements MessageConsumer
{
    /**
     * @var Destination
     */
    private $queue;

    /**
     * @param Destination $queue
     */
    public function __construct(Destination $queue)
    {
        $this->queue = $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return null
     */
    public function receive($timeout = 0)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function acknowledge(Message $message)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function reject(Message $message, $requeue = false)
    {
    }
}
