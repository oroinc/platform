<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Origin;

use Oro\Bundle\SyncBundle\Authentication\Origin\OriginProviderInterface;
use Oro\Bundle\SyncBundle\Authentication\Origin\OriginRegistryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OriginRegistryFactoryTest extends TestCase
{
    private OriginProviderInterface&MockObject $originProvider;
    private OriginRegistryFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->originProvider = $this->createMock(OriginProviderInterface::class);

        $this->factory = new OriginRegistryFactory($this->originProvider);
    }

    public function testCreateRegistry(): void
    {
        $this->originProvider->expects($this->once())
            ->method('getOrigins')
            ->willReturn(['origin1', 'origin2']);

        $registry = $this->factory->__invoke();
        self::assertEquals(['origin1', 'origin2'], $registry->getOrigins());
    }
}
