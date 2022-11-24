<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;

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
     * @dataProvider provideParameters
     */
    public function testAddParameters(ActionData $expected, array $parameters)
    {
        $instance = new ActionGroupExecutionArgs('someName');

        foreach ($parameters as $v) {
            [$name, $value] = $v;
            $instance->addParameter($name, $value);
        }

        $this->assertEquals($expected, $instance->getActionData());
    }

    public function testExecute()
    {
        $instance = new ActionGroupExecutionArgs('test_action_group', ['arg1' => 'val1']);
        $registry = $this->createMock(ActionGroupRegistry::class);
        $actionGroup = $this->createMock(ActionGroup::class);

        $registry->expects($this->once())
            ->method('get')
            ->with('test_action_group')
            ->willReturn($actionGroup);

        $errorsCollection = new ArrayCollection();

        $actionGroup->expects($this->once())
            ->method('execute')
            ->with(new ActionData(['arg1' => 'val1']), $errorsCollection)
            ->willReturn('ok');

        $this->assertEquals('ok', $instance->execute($registry, $errorsCollection));
    }

    public function provideParameters(): array
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
