<?php

namespace Oro\Component\Config\Tests\Unit\Loader;

use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContainerBuilderAdapterTest extends TestCase
{
    private ContainerBuilder&MockObject $container;
    private ContainerBuilderAdapter $adapter;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerBuilder::class);
        $this->adapter = new ContainerBuilderAdapter($this->container);
    }

    public function testGetResources(): void
    {
        $resources = [$this->createMock(ResourceInterface::class)];
        $this->container->expects(self::once())
            ->method('getResources')
            ->willReturn($resources);
        self::assertSame($resources, $this->adapter->getResources());
    }

    public function testAddResource(): void
    {
        $resource = $this->createMock(ResourceInterface::class);
        $this->container->expects(self::once())
            ->method('addResource')
            ->with(self::identicalTo($resource));
        $this->adapter->addResource($resource);
    }
}
