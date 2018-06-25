<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\ProcessorsPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProcessorsPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CompilerPassInterface
     */
    protected $pass;

    protected function setUp()
    {
        $this->pass = new ProcessorsPass();
    }

    protected function tearDown()
    {
        unset($this->pass);
    }

    public function testProcess()
    {
        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['getDefinition', 'findTaggedServiceIds'])
            ->getMock();
        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(ProcessorsPass::SYNC_PROCESSOR_REGISTRY)
            ->will($this->returnValue($definition));

        $services = ['testId' => [[ProcessorsPass::INTEGRATION_NAME => 'test']]];
        $containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ProcessorsPass::SYNC_PROCESSOR_TAG)
            ->will($this->returnValue($services));
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('addProcessor', ['test', new Reference('testId')]);

        $this->pass->process($containerBuilder);
    }

    /**
     * @expectedException \Oro\Bundle\IntegrationBundle\Exception\LogicException
     * @expectedExceptionMessage Could not retrieve type attribute for "testId"
     */
    public function testProcessException()
    {
        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['getDefinition', 'findTaggedServiceIds'])
            ->getMock();
        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(ProcessorsPass::SYNC_PROCESSOR_REGISTRY)
            ->will($this->returnValue($definition));

        $services = ['testId' => [[]]];
        $containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ProcessorsPass::SYNC_PROCESSOR_TAG)
            ->will($this->returnValue($services));

        $definition->expects($this->never())
            ->method('addMethodCall');

        $this->pass->process($containerBuilder);
    }
}
