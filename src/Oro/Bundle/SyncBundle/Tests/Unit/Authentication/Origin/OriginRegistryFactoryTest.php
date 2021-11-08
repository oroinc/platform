<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Origin;

use Oro\Bundle\SyncBundle\Authentication\Origin\OriginProviderInterface;
use Oro\Bundle\SyncBundle\Authentication\Origin\OriginRegistryFactory;

class OriginRegistryFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var OriginProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $originProvider;

    /** @var OriginRegistryFactory */
    private $factory;

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
