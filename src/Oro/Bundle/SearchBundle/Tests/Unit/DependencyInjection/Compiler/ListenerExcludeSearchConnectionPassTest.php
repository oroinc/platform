<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SearchBundle\DependencyInjection\Compiler\ListenerExcludeSearchConnectionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ListenerExcludeSearchConnectionPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ListenerExcludeSearchConnectionPass */
    protected $compiler;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder */
    protected $container;

    protected function setUp()
    {
        $this->compiler = new ListenerExcludeSearchConnectionPass();
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
            'missing parameter' => [false, ['search'], []],
            'existing parameter' => [true, ['session', 'search'], ['session']],
            'wrong type' => [true, ['session', 'search'], 'session'],
        ];
    }
}
