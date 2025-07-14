<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\EventListener;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\InterruptConsumptionExtension;
use Oro\Bundle\MessageQueueBundle\EventListener\UpdateSchemaListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

class UpdateSchemaListenerTest extends TestCase
{
    private CacheItemPoolInterface&MockObject $interruptConsumptionCache;
    private UpdateSchemaListener $listener;

    #[\Override]
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
