<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\ListenerExcludeConfigConnectionPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ListenerExcludeConfigConnectionPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var ListenerExcludeConfigConnectionPass */
    protected $compiler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder */
    protected $container;

    protected function setUp()
    {
        $this->compiler = new ListenerExcludeConfigConnectionPass();
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * @dataProvider processDataProvider
     * @param bool $hasParameter
     * @param array $expectedParameter
     * @param array $parameterValue
     */
    public function testProcess($hasParameter, array $expectedParameter, $parameterValue)
    {
        $this->container->expects($this->once())->method('hasParameter')->willReturn($hasParameter);
        if ($hasParameter) {
            $this->container->expects($this->once())->method('getParameter')->willReturn($parameterValue);
        }
        $this->container->expects($this->once())->method('setParameter')->with(
            $this->isType('string'),
            $this->equalTo($expectedParameter)
        );

        $this->compiler->process($this->container);
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        return [
            'missing parameter' => [false, ['config'], []],
            'existing parameter' => [true, ['session', 'config'], ['session']],
            'wrong type' => [true, ['session', 'config'], 'session'],
        ];
    }
}
