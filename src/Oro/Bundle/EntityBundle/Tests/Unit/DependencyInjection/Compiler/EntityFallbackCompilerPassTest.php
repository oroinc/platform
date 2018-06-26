<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityFallbackCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class EntityFallbackCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityFallbackCompilerPass
     */
    protected $entityFallbackCompilerPass;

    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject * */
    protected $container;

    protected function setUp()
    {
        $this->entityFallbackCompilerPass = new EntityFallbackCompilerPass();
        $this->container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testProcessSkipIfNoResolver()
    {
        $this->container->expects($this->once())->method('has')->willReturn(false);
        $this->container->expects($this->never())->method('findDefinition');

        $this->entityFallbackCompilerPass->process($this->container);
    }

    public function testProcessAddsProviders()
    {
        $providers = [
            '1' => [
                'tag1' => ['id' => 1],
                'tag2' => ['id' => 2],
            ],
        ];

        $this->container->expects($this->once())->method('has')->willReturn(true);
        $this->container->expects($this->once())->method('findTaggedServiceIds')->willReturn($providers);

        $resolver = $this->getMockBuilder(Definition::class)->getMock();
        $this->container->expects($this->once())->method('findDefinition')->willReturn($resolver);
        $resolver->expects($this->exactly(1))->method('addMethodCall')->with(
            'addFallbackProvider',
            [new Reference('1'), $providers['1']['tag1']['id']]
        );

        $this->entityFallbackCompilerPass->process($this->container);
    }
}
