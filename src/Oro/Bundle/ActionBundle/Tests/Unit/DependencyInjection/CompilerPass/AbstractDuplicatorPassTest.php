<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

abstract class AbstractDuplicatorPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CompilerPassInterface
     */
    protected $compilerPass;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder
     */
    protected $container;

    /**
     * @var \PHPUnit\Framework\MockObject\MockBuilder
     */
    protected $definitionBuilder;

    /**
     * @var string
     */
    protected $filterId = 'testFilter';

    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->definitionBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor();
    }

    public function testProcess()
    {
        $hasDefinition = true;
        $this->mockContainer($hasDefinition);
        $this->compilerPass->process($this->container);
    }

    /**
     * @param boolean $hasDefinition
     */
    protected function mockContainer($hasDefinition)
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->getServiceId())
            ->willReturn($hasDefinition);

        if ($hasDefinition) {
            $this->container->expects($this->once())
                ->method('findTaggedServiceIds')
                ->with($this->getTag())
                ->willReturn(
                    [
                        $this->filterId => [],
                    ]
                );
            $definition = $this->definitionBuilder->getMock();
            $definition->expects($this->once())
                ->method('addMethodCall')
                ->with('addObjectType', [new Reference($this->filterId)])
                ->willReturn($definition);

            $this->container->expects($this->any())
                ->method('getDefinition')
                ->will($this->returnValueMap([
                    [$this->getServiceId(), $definition],
                ]));
        } else {
            $this->container->expects($this->never())
                ->method('findTaggedServiceIds');
        }
    }

    /**
     * @return string
     */
    abstract protected function getServiceId();

    /**
     * @return string
     */
    abstract protected function getTag();
}
