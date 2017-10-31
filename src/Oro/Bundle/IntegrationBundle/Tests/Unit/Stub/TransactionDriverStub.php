<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Stub;

use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use Oro\Component\MessageQueue\Transport\TransactionInterface;

class TransactionDriverStub implements DriverInterface, TransactionInterface
{
    /**
     * {@inheritdoc}
     */
    public function createTransportMessage()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function send(QueueInterface $queue, Message $message)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function startTransaction()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
    }
}
