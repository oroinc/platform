<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\DataCollector;

use Oro\Bundle\LoggerBundle\DataCollector\LoggerDataCollector;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class LoggerDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    public function testLateCollect()
    {
        $logger = $this->createMock(DebugLoggerInterface::class);
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
