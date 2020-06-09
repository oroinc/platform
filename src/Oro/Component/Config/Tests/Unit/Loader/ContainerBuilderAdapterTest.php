<?php

namespace Oro\Component\Config\Tests\Unit\Loader;

use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContainerBuilderAdapterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var ContainerBuilderAdapter */
    private $adapter;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerBuilder::class);
        $this->adapter = new ContainerBuilderAdapter($this->container);
    }

    public function testGetResources()
    {
        $resources = [$this->createMock(ResourceInterface::class)];
        $this->container->expects(self::once())
            ->method('getResources')
            ->willReturn($resources);
        self::assertSame($resources, $this->adapter->getResources());
    }

    public function testAddResource()
    {
        $resource = $this->createMock(ResourceInterface::class);
        $this->container->expects(self::once())
            ->method('addResource')
            ->with(self::identicalTo($resource));
        $this->adapter->addResource($resource);
    }
}
