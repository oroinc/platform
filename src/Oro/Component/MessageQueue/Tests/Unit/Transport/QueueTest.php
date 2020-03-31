<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport;

use Oro\Component\MessageQueue\Transport\Queue;

class QueueTest extends \PHPUnit\Framework\TestCase
{
    public function testQueueName(): void
    {
        $queue = new Queue('queue name 1');
        $this->assertEquals('queue name 1', $queue->getQueueName());
    }
}
