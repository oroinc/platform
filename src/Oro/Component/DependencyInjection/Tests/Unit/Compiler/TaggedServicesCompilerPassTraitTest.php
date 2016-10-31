<?php

namespace Oro\Component\DependencyInjection\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;

class TaggedServicesCompilerPassTraitTest extends \PHPUnit_Framework_TestCase
{
    use TaggedServicesCompilerPassTrait;

    /** @var Definition|\PHPUnit_Framework_MockObject_MockObject */
    protected $service;

    /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $builder;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->builder = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $this->service = $this->getMockBuilder(Definition::class)->getMock();
    }

    public function testRegisterTaggedServicesAndNoServiceDefinition()
    {
        $this->builder->expects($this->once())->method('hasDefinition')->with('service1')->willReturn(false);
        $this->builder->expects($this->never())->method('findTaggedServiceIds');

        $this->registerTaggedServices($this->builder, 'service1', 'tag1', 'addExtension');
    }

    public function testRegisterTaggedServicesAndNoTaggedServices()
    {
        $this->builder->expects($this->once())->method('hasDefinition')->willReturn(true);
        $this->builder->expects($this->once())->method('findTaggedServiceIds')->with('tag1')->willReturn(null);
        $this->builder->expects($this->never())->method('getDefinition');

        $this->registerTaggedServices($this->builder, 'service1', 'tag1', 'addExtension');
    }

    /**
     * @dataProvider taggedServicesDataProvider
     */
    public function testRegisterTaggedServices($taggedServices)
    {
        $this->builder->expects($this->once())->method('hasDefinition')->willReturn(true);
        $this->builder->expects($this->once())->method('findTaggedServiceIds')
            ->willReturn($taggedServices);
        $this->builder->expects($this->once())->method('getDefinition')->with('service1')->willReturn($this->service);

        $this->service->expects($this->exactly(3))->method('addMethodCall');

        $this->service->expects($this->at(0))->method('addMethodCall')
            ->with('addExtension', [new Reference('taggedService2'), 'taggedService2']);

        $this->service->expects($this->at(1))->method('addMethodCall')
            ->with('addExtension', [new Reference('taggedService3'), 'taggedService3Alias']);

        $this->service->expects($this->at(2))->method('addMethodCall')
            ->with('addExtension', [new Reference('taggedService1'), 'taggedService1Alias']);

        $this->registerTaggedServices($this->builder, 'service1', 'tag1', 'addExtension');
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
