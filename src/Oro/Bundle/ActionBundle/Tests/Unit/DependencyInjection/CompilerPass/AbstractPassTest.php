<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\AbstractPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

abstract class AbstractPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder */
    protected $container;

    /** @var \PHPUnit\Framework\MockObject\MockBuilder */
    protected $definitionBuilder;

    /** @var AbstractPass */
    protected $compilerPass;

    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->definitionBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor();
    }

    protected function tearDown()
    {
        unset($this->compilerPass, $this->definitionBuilder, $this->container);
    }

    public function testProcess()
    {
        $extensionDefinition = $this->definitionBuilder->getMock();
        $extensionDefinition->expects($this->once())
            ->method('replaceArgument')
            ->with(
                1,
                [
                    'service_first' => 'service.definition.first',
                    'service_first_alias' => 'service.definition.first',
                    'service.definition.second' => 'service.definition.second'
                ]
            );

        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->getServiceId())
            ->willReturn(true);
        $this->container->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValueMap([
                [$this->getServiceId(), $extensionDefinition],
                ['service.definition.first', $this->createServiceDefinition()],
                ['service.definition.second', $this->createServiceDefinition()],
            ]));
        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->getTag())
            ->willReturn(
                [
                    'service.definition.first' => [['alias' => 'service_first|service_first_alias']],
                    'service.definition.second' => [[]],
                ]
            );

        $this->compilerPass->process($this->container);
    }

    public function testProcessWithoutConfigurationProvider()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->getServiceId())
            ->willReturn(false);
        $this->container->expects($this->never())
            ->method('getDefinition')
            ->with($this->anything());
        $this->container->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($this->container);
    }

    /**
     * @return string
     */
    abstract protected function getServiceId();

    /**
     * @return string
     */
    abstract protected function getTag();

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Definition
     */
    protected function createServiceDefinition()
    {
        $definition = $this->definitionBuilder->getMock();
        $definition->expects($this->once())
            ->method('setShared')
            ->with(false)
            ->willReturnSelf();
        $definition->expects($this->once())
            ->method('setPublic')
            ->with(false)
            ->willReturnSelf();

        return $definition;
    }
}
