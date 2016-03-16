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

    /**
     * @return array
     */
    public function provideParameters()
    {
        return [
            'no args' => [
                'expected' => new ActionData(['data' => (object)[]]),
                'parameters' => []
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
                'parameters' => [
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
                'parameters' => [
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
