<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\UserBundle\DependencyInjection\Compiler\PrivilegeCategoryPass;

class PrivilegeCategoryPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var PrivilegeCategoryPass */
    protected $compilerPass;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder */
    protected $container;

    protected function setUp()
    {
        $this->container = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $this->compilerPass = new PrivilegeCategoryPass();
    }

    protected function tearDown()
    {
        unset($this->container, $this->compilerPass);
    }

    public function testServiceNotExists()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(PrivilegeCategoryPass::REGISTRY_SERVICE))
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
            ->with($this->equalTo(PrivilegeCategoryPass::REGISTRY_SERVICE))
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(PrivilegeCategoryPass::TAG))
            ->will($this->returnValue([]));

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testServiceExistsWithTaggedServices()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(PrivilegeCategoryPass::REGISTRY_SERVICE))
            ->will($this->returnValue(true));

        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo(PrivilegeCategoryPass::REGISTRY_SERVICE))
            ->will($this->returnValue($definition));

        $taggedServices = [
            'service.name.1' => [[]],
            'service.name.2' => [[]],
        ];

        $definition
            ->expects($this->exactly(2))
            ->method('addMethodCall')
            ->withConsecutive(
                ['addProvider', [new Reference('service.name.1')]],
                ['addProvider', [new Reference('service.name.2')]]
            );

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(PrivilegeCategoryPass::TAG))
            ->will($this->returnValue($taggedServices));

        $this->compilerPass->process($this->container);
    }
}
