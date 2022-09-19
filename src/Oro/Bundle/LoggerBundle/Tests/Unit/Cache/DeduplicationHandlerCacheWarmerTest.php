<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Cache;

use Monolog\Handler\DeduplicationHandler;
use Oro\Bundle\LoggerBundle\Cache\DeduplicationHandlerCacheWarmer;

class DeduplicationHandlerCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DeduplicationHandlerCacheWarmer */
    private $warmer;

    /** @var DeduplicationHandler */
    private $deduplicationHandler;

    protected function setUp(): void
    {
        $this->deduplicationHandler = $this->createMock(DeduplicationHandler::class);
        $this->warmer = new DeduplicationHandlerCacheWarmer($this->deduplicationHandler);
    }

    public function testWarmUp(): void
    {
        $this->deduplicationHandler->expects($this->once())
            ->method('flush');

        $this->warmer->warmUp('test');
    }

    public function testIsOptional(): void
    {
        $this->assertFalse($this->warmer->isOptional());
    }
}
