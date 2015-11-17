<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\GeneratedValueStrategyListenerPass;

class GeneratedValueStrategyListenerPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var GeneratedValueStrategyListenerPass */
    protected $compiler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder */
    protected $container;

    protected function setUp()
    {
        $this->compiler = new GeneratedValueStrategyListenerPass();
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * @dataProvider processDataProvider
     * @param array $parameterValue
     */
    public function testProcess($parameterValue)
    {
        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $this->container->expects($this->once())->method('hasDefinition')->willReturn(true);
        $this->container->expects($this->once())->method('getDefinition')->willReturn($definition);
        $this->container->expects($this->once())->method('hasParameter')->willReturn(true);
        $this->container->expects($this->once())->method('getParameter')->willReturn($parameterValue);

        $definition->expects($this->once())->method('clearTag')->with($this->isType('string'));
        $definition
            ->expects($this->exactly(count($parameterValue)))
            ->method('addTag')
            ->with(
                $this->isType('string'),
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->arrayHasKey('event'),
                    $this->arrayHasKey('connection')
                )
            );

        $this->compiler->process($this->container);
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        return [
            'type' => ['session'],
            'empty' => [[]],
            'value' => [['session']],
        ];
    }

    public function testProcessWithoutDefinition()
    {
        $this->container->expects($this->once())->method('hasDefinition')->willReturn(false);
        $this->container->expects($this->never())->method('getDefinition');
        $this->container->expects($this->never())->method('hasParameter');
        $this->container->expects($this->never())->method('getParameter');

        $this->compiler->process($this->container);
    }

    public function testProcessWithoutParameter()
    {
        $this->container->expects($this->once())->method('hasDefinition')->willReturn(true);
        $this->container->expects($this->never())->method('getDefinition');
        $this->container->expects($this->once())->method('hasParameter')->willReturn(false);
        $this->container->expects($this->never())->method('getParameter');

        $this->compiler->process($this->container);
    }
}
