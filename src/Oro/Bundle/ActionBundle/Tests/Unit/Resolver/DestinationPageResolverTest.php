<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Resolver;

use Oro\Bundle\ActionBundle\Resolver\DestinationPageResolver;
use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Symfony\Component\Routing\RouterInterface;

class DestinationPageResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityConfigHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityConfigHelper;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var DestinationPageResolver */
    protected $resolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->entityConfigHelper = $this->createMock(EntityConfigHelper::class);

        $this->resolver = new DestinationPageResolver($this->entityConfigHelper, $this->router);
    }

    public function testGetAvailableDestinationsForEntity()
    {
        $this->entityConfigHelper->expects($this->once())
            ->method('getRoutes')
            ->with('TestClass', ['view', 'name'])
            ->willReturn(['name' => 'index_route', 'view' => 'view_route', 'custom' => null]);

        $this->assertEquals([null, 'name', 'view'], $this->resolver->getAvailableDestinationsForEntity('TestClass'));
    }

    public function testGetAvailableDestinationsForEntityWithoutRoutes()
    {
        $this->entityConfigHelper->expects($this->once())
            ->method('getRoutes')
            ->with('TestClass', ['view', 'name'])
            ->willReturn(['custom' => 'custom_route']);

        $this->assertEquals([null, 'custom'], $this->resolver->getAvailableDestinationsForEntity('TestClass'));
    }

    public function testResolveDestinationUrl()
    {
        $entity = new Item();
        $entity->id = 10;

        $this->entityConfigHelper->expects($this->any())
            ->method('getRoutes')
            ->willReturn(['name' => 'index_route', 'view' => 'view_route']);

        $this->router->expects($this->any())->method('generate')
            ->will($this->returnValueMap([
                ['index_route', [], RouterInterface::ABSOLUTE_PATH, 'example.com/index'],
                ['view_route', ['id' => 10], RouterInterface::ABSOLUTE_PATH, 'example.com/view'],
            ]));

        $this->assertEquals('example.com/index', $this->resolver->resolveDestinationUrl($entity, 'name'));
        $this->assertEquals('example.com/view', $this->resolver->resolveDestinationUrl($entity, 'view'));
        $this->assertEquals(null, $this->resolver->resolveDestinationUrl($entity, 'unknown_route'));
        $this->assertEquals(null, $this->resolver->resolveDestinationUrl(new Item(), 'view'));
    }
}
