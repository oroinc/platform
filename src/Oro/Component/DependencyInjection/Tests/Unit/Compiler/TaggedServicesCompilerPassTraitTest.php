<?php

namespace Oro\Component\DependencyInjection\Tests\Unit;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TaggedServicesCompilerPassTraitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TaggedServicesCompilerPassTrait|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $trait;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->trait = $this->getMockBuilder(TaggedServicesCompilerPassTrait::class)
            ->getMockForTrait();
    }

    public function testRegisterTaggedServicesAndNoServiceDefinition()
    {
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $container->expects($this->once())->method('hasDefinition')->with('service1')->willReturn(false);
        $container->expects($this->never())->method('findTaggedServiceIds');

        $this->trait->registerTaggedServices($container, 'service1', 'tag1', 'addExtension');
    }

    public function testRegisterTaggedServicesAndNoTaggedServices()
    {
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $container->expects($this->once())->method('hasDefinition')->willReturn(true);
        $container->expects($this->once())->method('findTaggedServiceIds')->with('tag1')->willReturn([]);
        $container->expects($this->never())->method('getDefinition');

        $this->trait->registerTaggedServices($container, 'service1', 'tag1', 'addExtension');
    }

    /**
     * @dataProvider taggedServicesDataProvider
     * @param array $taggedServices
     */
    public function testRegisterTaggedServices(array $taggedServices)
    {
        $service = $this->getMockBuilder(Definition::class)
            ->getMock();

        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $container->expects($this->once())->method('hasDefinition')->willReturn(true);
        $container->expects($this->once())->method('findTaggedServiceIds')
            ->willReturn($taggedServices);
        $container->expects($this->once())->method('getDefinition')->with('service1')->willReturn($service);

        $service->expects($this->exactly(3))->method('addMethodCall');

        $service->expects($this->at(0))->method('addMethodCall')
            ->with('addExtension', [new Reference('taggedService2'), 'taggedService2']);

        $service->expects($this->at(1))->method('addMethodCall')
            ->with('addExtension', [new Reference('taggedService3'), 'taggedService3Alias']);

        $service->expects($this->at(2))->method('addMethodCall')
            ->with('addExtension', [new Reference('taggedService1'), 'taggedService1Alias']);

        $this->trait->registerTaggedServices($container, 'service1', 'tag1', 'addExtension');
    }

    /**
     * @return array
     */
    public function taggedServicesDataProvider()
    {
        return [
            'one without priority and without alias' => [
                [
                    'taggedService1' => [
                        ['priority' => 20, 'alias' => 'taggedService1Alias'],
                    ],
                    'taggedService2' => [
                    ],
                    'taggedService3' => [
                        ['priority' => 10, 'alias' => 'taggedService3Alias'],
                    ],
                ],
            ],
            'all without priorities' => [
                [
                    'taggedService2' => [],
                    'taggedService3' => [['alias' => 'taggedService3Alias']],
                    'taggedService1' => [['alias' => 'taggedService1Alias']],
                ],
            ],
            'with duplicated priorities' => [
                [
                    'taggedService2' => [['priority' => 10]],
                    'taggedService3' => [['priority' => 10, 'alias' => 'taggedService3Alias']],
                    'taggedService1' => [['priority' => 10, 'alias' => 'taggedService1Alias']],
                ],
            ],
        ];
    }
}
