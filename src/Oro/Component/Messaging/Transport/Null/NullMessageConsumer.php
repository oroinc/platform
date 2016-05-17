<?php
namespace Oro\Component\Messaging\Transport\Null;

use Oro\Component\Messaging\Transport\Destination;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\MessageConsumer;

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
