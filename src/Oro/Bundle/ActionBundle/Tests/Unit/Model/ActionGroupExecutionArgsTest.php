<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;

class ActionGroupExecutionArgsTest extends \PHPUnit_Framework_TestCase
{
    public function testNameConstruction()
    {
        $expected = 'nameOfActionGroup';

        $instance = new ActionGroupExecutionArgs($expected);

        $this->assertEquals($expected, $instance->getName());
    }

    /**
     * @param $expected
     * @dataProvider provideArguments
     */
    public function testArguments($expected)
    {
        $instance = new ActionGroupExecutionArgs('someName');

        $args = func_get_args();
        array_shift($args);
        foreach ($args as $v) {
            list($name, $value) = $v;
            $instance->addArgument($name, $value);
        }

        $this->assertEquals($expected, $instance->getArguments());
    }

    /**
     * @return array
     */
    public function provideArguments()
    {
        return [
            'no args' => [
                'expected' => []
            ],
            'few' => [
                'expected' => [
                    'arg1' => 'val1',
                    'arg2' => 'val2'
                ],
                ['arg1', 'val1'],
                ['arg2', 'val2'],
            ],
            'many' => [
                'expected' => [
                    'arg1' => 'val1',
                    'arg2' => 'val1',
                    'arg3' => 'val1',
                    'arg4' => 'val1',
                ],
                ['arg1', 'val1'],
                ['arg2', 'val1'],
                ['arg3', 'val1'],
                ['arg4', 'val1'],
            ],
            'overrides' => [
                'expected' => [
                    'arg1' => 'val2'
                ],
                [
                    'arg1',
                    'val1'
                ],
                [
                    'arg1',
                    'val2'
                ]
            ]
        ];
    }
}
