<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\InlineEditColumnOptionsGuesserPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class InlineEditColumnOptionsGuesserPassTest
 * @package Oro\Bundle\DatagridBundle\Tests\Unit\DependencyInjection\CompilerPass
 */
class InlineEditColumnOptionsGuesserPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InlineEditColumnOptionsGuesserPass
     */
    protected $compilerPass;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder
     */
    protected $container;

    protected function setUp()
    {
        $this->container = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $this->compilerPass = new InlineEditColumnOptionsGuesserPass();
    }

    public function testServiceNotExists()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(InlineEditColumnOptionsGuesserPass::INLINE_EDIT_COLUMN_OPTIONS_GUESSER_SERVICE))
            ->will($this->returnValue(false));

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->container->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($this->container);
    }

    public function testServiceExistsNotTaggedServices()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(InlineEditColumnOptionsGuesserPass::INLINE_EDIT_COLUMN_OPTIONS_GUESSER_SERVICE))
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(InlineEditColumnOptionsGuesserPass::TAG))
            ->will($this->returnValue([]));

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testServiceExistsWithTaggedServices()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(InlineEditColumnOptionsGuesserPass::INLINE_EDIT_COLUMN_OPTIONS_GUESSER_SERVICE))
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(InlineEditColumnOptionsGuesserPass::TAG))
            ->will($this->returnValue(['service' => ['class' => '\stdClass']]));

        $definition = $this->createMock('Symfony\Component\DependencyInjection\Definition');

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo(InlineEditColumnOptionsGuesserPass::INLINE_EDIT_COLUMN_OPTIONS_GUESSER_SERVICE))
            ->will($this->returnValue($definition));

        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with($this->isType('string'), $this->isType('array'));

        $this->compilerPass->process($this->container);
    }
}
