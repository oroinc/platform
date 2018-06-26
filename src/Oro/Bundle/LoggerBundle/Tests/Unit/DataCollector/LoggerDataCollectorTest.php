<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\DataCollector;

use Oro\Bundle\LoggerBundle\DataCollector\LoggerDataCollector;
use Oro\Bundle\LoggerBundle\Tests\Unit\Stub\DebugLoggerStub;

class LoggerDataCollectorTest extends \PHPUnit\Framework\TestCase
{
    public function testLateCollect()
    {
        $logger = $this->createMock(DebugLoggerStub::class);
        $logger->expects($this->once())
            ->method('countErrors');
        $logger->expects($this->any())
            ->method('getLogs')
            ->willReturn([]);

        $collector = new LoggerDataCollector($logger);
        $collector->lateCollect();
        $collector->lateCollect();
    }
}
