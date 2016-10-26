<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

abstract class AbstractExtensionCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    const TAGGED_SERVICE_1 = 'test.tagged.service.first';
    const TAGGED_SERVICE_2 = 'test.tagged.service.second';
    const TAGGED_SERVICE_3 = 'test.tagged.service.third';

    /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $containerBuilder;

    /** @var Definition|\PHPUnit_Framework_MockObject_MockObject */
    protected $serviceDefinition;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->containerBuilder = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serviceDefinition = $this->getMockBuilder(Definition::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return CompilerPassInterface
     */
    abstract protected function getCompilerPass();

    /**
     * @return string
     */
    abstract protected function getServiceId();

    /**
     * @return string
     */
    abstract protected function getTagName();

    public function testProcessWithoutListenerDefinition()
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with($this->getServiceId())
            ->willReturn(false);
        $this->containerBuilder->expects($this->never())->method('getDefinition');
        $this->containerBuilder->expects($this->never())->method('findTaggedServiceIds');

        $this->getCompilerPass()->process($this->containerBuilder);
    }

    /**
     * @param string $method
     */
    protected function assertServiceDefinitionMethodCalled($method)
    {
        $extensions = [
            [new Reference(self::TAGGED_SERVICE_2), self::TAGGED_SERVICE_2],
            [new Reference(self::TAGGED_SERVICE_1), self::TAGGED_SERVICE_1],
            [new Reference(self::TAGGED_SERVICE_3), self::TAGGED_SERVICE_3]
        ];

        foreach ($extensions as $key => $extension) {
            $this->serviceDefinition->expects($this->at($key))->method('addMethodCall')->with($method, $extension);
        }
    }

    protected function assertConteinerBuilderCalled()
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with($this->getServiceId())
            ->willReturn(true);
        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with($this->getServiceId())
            ->willReturn($this->serviceDefinition);
        $this->containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->getTagName())
            ->willReturn(
                [
                    self::TAGGED_SERVICE_1 => [['priority' => 20]],
                    self::TAGGED_SERVICE_2 => [['priority' => 10]],
                    self::TAGGED_SERVICE_3 => [['priority' => 20]]
                ]
            );
    }
}
