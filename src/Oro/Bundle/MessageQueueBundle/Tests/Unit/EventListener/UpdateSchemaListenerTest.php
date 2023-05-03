<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\EventListener;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\InterruptConsumptionExtension;
use Oro\Bundle\MessageQueueBundle\EventListener\UpdateSchemaListener;
use Psr\Cache\CacheItemPoolInterface;

class UpdateSchemaListenerTest extends \PHPUnit\Framework\TestCase
{
    private CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject $interruptConsumptionCache;

    private UpdateSchemaListener $listener;

    protected function setUp(): void
    {
        $this->interruptConsumptionCache = $this->createMock(CacheItemPoolInterface::class);

        $this->listener = new UpdateSchemaListener($this->interruptConsumptionCache);
    }

    public function testOnSchemaUpdate(): void
    {
        $this->interruptConsumptionCache->expects(self::once())
            ->method('deleteItem')
            ->with(InterruptConsumptionExtension::CACHE_KEY);

        $this->listener->onSchemaUpdate();
    }
}
