<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TaggedServicesCompilerPassTraitTest extends \PHPUnit\Framework\TestCase
{
    use TaggedServicesCompilerPassTrait;

    public function testRegisterTaggedServicesAndNoServiceDefinition()
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())->method('hasDefinition')->with('service1')->willReturn(false);
        $container->expects($this->never())->method('findTaggedServiceIds');

        $this->registerTaggedServices($container, 'service1', 'tag1', 'addExtension');
    }

    public function testRegisterTaggedServicesAndNoTaggedServices()
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())->method('hasDefinition')->willReturn(true);
        $container->expects($this->once())->method('findTaggedServiceIds')->with('tag1')->willReturn([]);
        $container->expects($this->never())->method('getDefinition');

        $this->registerTaggedServices($container, 'service1', 'tag1', 'addExtension');
    }

    /**
     * @dataProvider taggedServicesDataProvider
     */
    public function testRegisterTaggedServices(array $taggedServices)
    {
        $container = new ContainerBuilder();
        $service = $container->register('service1');
        foreach ($taggedServices as $id => $attributes) {
            $container->register($id)->addTag('tag1', $attributes);
        }

        $this->registerTaggedServices($container, 'service1', 'tag1', 'addExtension');

        $this->assertEquals(
            [
                ['addExtension', [new Reference('taggedService2'), 'taggedService2']],
                ['addExtension', [new Reference('taggedService3'), 'taggedService3Alias']],
                ['addExtension', [new Reference('taggedService1'), 'taggedService1Alias']]
            ],
            $service->getMethodCalls()
        );
    }

    /**
     * @return array
     */
    public function taggedServicesDataProvider()
    {
        return [
            'one without priority and without alias' => [
                [
                    'taggedService1' => ['priority' => 20, 'alias' => 'taggedService1Alias'],
                    'taggedService2' => [],
                    'taggedService3' => ['priority' => 10, 'alias' => 'taggedService3Alias']
                ]
            ],
            'all without priorities' => [
                [
                    'taggedService2' => [],
                    'taggedService3' => ['alias' => 'taggedService3Alias'],
                    'taggedService1' => ['alias' => 'taggedService1Alias'],
                ]
            ],
            'with duplicated priorities' => [
                [
                    'taggedService2' => ['priority' => 10],
                    'taggedService3' => ['priority' => 10, 'alias' => 'taggedService3Alias'],
                    'taggedService1' => ['priority' => 10, 'alias' => 'taggedService1Alias']
                ]
            ]
        ];
    }

    /**
     * @dataProvider findAndSortTaggedServicesDataProvider
     */
    public function testFindAndSortTaggedServices(array $taggedServices, $expectedResult)
    {
        $container = new ContainerBuilder();
        foreach ($taggedServices as $id => $attributes) {
            $container->register($id)->addTag('tag1', $attributes);
        }

        $this->assertEquals(
            $expectedResult,
            $this->findAndInverseSortTaggedServices('tag1', $container)
        );
    }

    /**
     * @return array
     */
    public function findAndSortTaggedServicesDataProvider()
    {
        return [
            'empty'         => [
                'taggedServices' => [],
                'expectedResult' => []
            ],
            'with priority' => [
                'taggedServices' => [
                    'taggedService2' => [],
                    'taggedService3' => ['priority' => 10],
                    'taggedService1' => ['priority' => -10]
                ],
                'expectedResult' => [
                    new Reference('taggedService1'),
                    new Reference('taggedService2'),
                    new Reference('taggedService3')
                ]
            ]
        ];
    }
}
