<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;

class ActionGroupExecutionArgsTest extends \PHPUnit_Framework_TestCase
{
    public function testNameConstruction()
    {
        $expected = 'nameOfActionGroup';

        $instance = new ActionGroupExecutionArgs($expected);

        $this->assertEquals($expected, $instance->getName());
    }

    public function testGetArgumentsCreateNewInstanceOfActionData()
    {
        $instance = new ActionGroupExecutionArgs('name');

        $this->assertNotSame($instance->getActionData(), $instance->getActionData());
    }

    /**
     * @param $expected
     * @param array $arguments
     * @dataProvider provideArguments
     */
    public function testAddArguments($expected, array $arguments)
    {
        $instance = new ActionGroupExecutionArgs('someName');

        foreach ($arguments as $v) {
            list($name, $value) = $v;
            $instance->addArgument($name, $value);
        }

        $this->assertEquals($expected, $instance->getActionData());
    }

    /**
     * @return array
     */
    public function provideArguments()
    {
        return [
            'no args' => [
                'expected' => new ActionData(['data' => (object)[]]),
                'arguments' => []
            ],
            'few' => [
                'expected' => new ActionData(
                    [
                        'data' => (object)[
                            'arg1' => 'val1',
                            'arg2' => 'val2'
                        ]
                    ]
                ),
                'arguments' => [
                    ['arg1', 'val1'],
                    ['arg2', 'val2']
                ]
            ],
            'many' => [
                'expected' => new ActionData(
                    [
                        'data' => (object)[
                            'arg1' => 'val1',
                            'arg2' => 'val1',
                            'arg3' => 'val1',
                            'arg4' => 'val1',
                        ]
                    ]
                ),
                'arguments' => [
                    ['arg1', 'val1'],
                    ['arg2', 'val1'],
                    ['arg3', 'val1'],
                    ['arg4', 'val1']
                ],
            ],
            'overrides' => [
                'expected' => new ActionData(
                    [
                        'data' => (object)[
                            'arg1' => 'val2'
                        ]
                    ]
                ),
                'arguments' => [
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
