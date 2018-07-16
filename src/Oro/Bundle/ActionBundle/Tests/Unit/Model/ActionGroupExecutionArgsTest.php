<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;

class ActionGroupExecutionArgsTest extends \PHPUnit\Framework\TestCase
{
    public function testNameConstruction()
    {
        $expected = 'nameOfActionGroup';

        $instance = new ActionGroupExecutionArgs($expected);

        $this->assertEquals($expected, $instance->getActionGroupName());
    }

    public function testGetParametersCreateNewInstanceOfActionData()
    {
        $instance = new ActionGroupExecutionArgs('name');

        $this->assertNotSame($instance->getActionData(), $instance->getActionData());
    }

    /**
     * @param $expected
     * @param array $parameters
     * @dataProvider provideParameters
     */
    public function testAddParameters($expected, array $parameters)
    {
        $instance = new ActionGroupExecutionArgs('someName');

        foreach ($parameters as $v) {
            list($name, $value) = $v;
            $instance->addParameter($name, $value);
        }

        $this->assertEquals($expected, $instance->getActionData());
    }

    public function testExecute()
    {
        $instance = new ActionGroupExecutionArgs('test_action_group', ['arg1' => 'val1']);

        $mockRegistry = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroupRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $mockActionGroup = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroup')
            ->disableOriginalConstructor()
            ->getMock();

        $mockRegistry->expects($this->once())
            ->method('get')
            ->with('test_action_group')
            ->willReturn($mockActionGroup);

        $errorsCollection = new ArrayCollection();

        $mockActionGroup->expects($this->once())->method('execute')
            ->with(new ActionData(['arg1' => 'val1']), $errorsCollection)
            ->willReturn('ok');

        $this->assertEquals('ok', $instance->execute($mockRegistry, $errorsCollection));
    }

    /**
     * @return array
     */
    public function provideParameters()
    {
        return [
            'no args' => [
                'expected' => new ActionData(),
                'parameters' => []
            ],
            'few' => [
                'expected' => new ActionData(
                    [
                        'arg1' => 'val1',
                        'arg2' => 'val2',
                    ]
                ),
                'parameters' => [
                    ['arg1', 'val1'],
                    ['arg2', 'val2'],
                ]
            ],
            'many' => [
                'expected' => new ActionData(
                    [
                        'arg1' => 'val1',
                        'arg2' => 'val1',
                        'arg3' => 'val1',
                        'arg4' => 'val1',
                    ]
                ),
                'parameters' => [
                    ['arg1', 'val1'],
                    ['arg2', 'val1'],
                    ['arg3', 'val1'],
                    ['arg4', 'val1'],
                ],
            ],
            'overrides' => [
                'expected' => new ActionData(
                    [
                        'arg1' => 'val2',
                    ]
                ),
                'parameters' => [
                    [
                        'arg1',
                        'val1'
                    ],
                    [
                        'arg1',
                        'val2'
                    ]
                ]
            ]
        ];
    }
}
