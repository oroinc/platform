<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass;

class UpdateDoctrineEventHandlersPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var UpdateDoctrineEventHandlersPass */
    protected $compiler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder */
    protected $container;

    protected function setUp()
    {
        $this->compiler = new UpdateDoctrineEventHandlersPass();
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * @param bool $hasConnectionParameter
     * @param bool $hasExcludedParameter
     * @param mixed $connectionParameter
     * @param mixed $excludedParameter
     * @param array $tags
     *
     * @dataProvider dataProvider
     */
    public function testProcess(
        $hasConnectionParameter,
        $hasExcludedParameter,
        $connectionParameter,
        $excludedParameter,
        array $tags = []
    ) {
        $this->container->expects($this->any())->method('hasParameter')
            ->willReturnOnConsecutiveCalls($hasConnectionParameter, $hasExcludedParameter);
        $this->container->expects($this->any())->method('getParameter')
            ->willReturnOnConsecutiveCalls($connectionParameter, $excludedParameter);

        $this->container->expects($this->any())->method('findTaggedServiceIds')
            ->willReturn(['id' => ['event' => ['tag1' => []]]]);

        $definition = new Definition();
        $this->container->expects($this->any())->method('getDefinition')->willReturn($definition);

        $this->compiler->process($this->container);

        $this->assertEquals($tags, $definition->getTags());
    }

    /** @return array */
    public function dataProvider()
    {
        return [
            'missing connections' => [false, false, [], [], []],
            'missing excluded connections' => [true, false, [], [], []],
            'has parameters' => [true, true, [], [], []],
            'exclude all' => [true, true, ['connection1' => []], ['connection1'], []],
            'exclude one' => [
                true,
                true,
                ['connection1' => [], 'connection2' => []],
                ['connection1'],
                [
                    'doctrine.event_subscriber' => [['tag1' => [], 'connection' => 'connection2']],
                    'doctrine.event_listener' => [['tag1' => [], 'connection' => 'connection2']],
                ],
            ],
            'exclude session' => [
                true,
                true,
                ['connection1' => [], 'connection2' => [], 'session' => []],
                ['connection1'],
                [
                    'doctrine.event_subscriber' => [['tag1' => [], 'connection' => 'connection2']],
                    'doctrine.event_listener' => [['tag1' => [], 'connection' => 'connection2']],
                ],
            ],
        ];
    }
}
