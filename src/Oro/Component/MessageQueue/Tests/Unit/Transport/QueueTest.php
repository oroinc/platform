<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport;

use Oro\Component\MessageQueue\Transport\Queue;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    public function testQueueName(): void
    {
        $queue = new Queue('queue name 1');
        $this->assertEquals('queue name 1', $queue->getQueueName());
    }
}
