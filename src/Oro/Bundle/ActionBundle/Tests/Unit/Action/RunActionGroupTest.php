<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Action\RunActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class RunActionGroupTest extends \PHPUnit\Framework\TestCase
{
    const ACTION_GROUP_NAME = 'test_action_group';

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionGroupRegistry */
    protected $mockActionGroupRegistry;

    /** @var RunActionGroup */
    protected $actionGroup;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->mockActionGroupRegistry = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroupRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->actionGroup = new RunActionGroup($this->mockActionGroupRegistry, new ContextAccessor());
        $this->actionGroup->setDispatcher($this->eventDispatcher);
    }

    protected function tearDown()
    {
        unset($this->actionGroup, $this->eventDispatcher, $this->mockActionGroupRegistry);
    }

    public function testOptionNamesRequirements()
    {
        $this->assertEquals(RunActionGroup::OPTION_ACTION_GROUP, 'action_group');
        $this->assertEquals(RunActionGroup::OPTION_PARAMETERS_MAP, 'parameters_mapping');
        $this->assertEquals(RunActionGroup::OPTION_RESULTS, 'results');
        $this->assertEquals(RunActionGroup::OPTION_RESULT, 'result');
    }

    public function testInitialize()
    {
        $parametersMap = [
            'entity_class' => 'testClass',
            'entity_id' => 1
        ];

        $options = [
            RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_GROUP_NAME,
            RunActionGroup::OPTION_PARAMETERS_MAP => $parametersMap,
            RunActionGroup::OPTION_RESULTS => [],
            RunActionGroup::OPTION_RESULT => new PropertyPath('path')
        ];

        $this->mockActionGroupRegistry->expects($this->once())
            ->method('getNames')
            ->willReturn([self::ACTION_GROUP_NAME]);

        $this->assertInstanceOf(
            'Oro\Component\Action\Action\ActionInterface',
            $this->actionGroup->initialize($options)
        );

        $this->assertAttributeInstanceOf(
            'Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs',
            'executionArgs',
            $this->actionGroup
        );
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     *
     * @param array $inputData
     * @param string $exception
     * @param string $exceptionMessage
     */
    public function testInitializeException(array $inputData, $exception, $exceptionMessage)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);

        $this->mockActionGroupRegistry
            ->expects($this->once())
            ->method('getNames')
            ->willReturn([self::ACTION_GROUP_NAME]);

        $this->actionGroup->initialize($inputData);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
    {
        $mockGroup = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroup')
            ->disableOriginalConstructor()
            ->getMock();

        return [
            'no action group name' => [
                'inputData' => [],
                'expectedException' => 'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                'expectedExceptionMessage' => sprintf(
                    'The required option "%s" is missing.',
                    RunActionGroup::OPTION_ACTION_GROUP
                )
            ],
            'action group does not exists' => [
                'inputData' => [
                    RunActionGroup::OPTION_ACTION_GROUP => 'non existent'
                ],
                'expectedException' => 'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                'expectedExceptionMessage' => 'The option "action_group" with value "non existent" is invalid. ' .
                    'Accepted values are: "test_action_group".'
            ],
            'bad parameters map type' => [
                'inputData' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_GROUP_NAME,
                    RunActionGroup::OPTION_PARAMETERS_MAP => 'string is not supported'
                ],
                'expectedException' => 'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                'expectedExceptionMessage' => sprintf(
                    'The option "%s" with value "string is not supported" ' .
                    'is expected to be of type "array", but is of type "string".',
                    RunActionGroup::OPTION_PARAMETERS_MAP
                ),
                $mockGroup
            ],
            'bad attribute' => [
                'inputData' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_GROUP_NAME,
                    RunActionGroup::OPTION_RESULT => '$.nonConvertedPropertyPath'
                ],
                'expectedException' => 'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                'expectedExceptionMessage' => sprintf(
                    'The option "%s" with value "$.nonConvertedPropertyPath"' .
                    ' is expected to be of type "null" or "Symfony\Component\PropertyAccess\PropertyPathInterface"',
                    RunActionGroup::OPTION_RESULT
                )
            ]
        ];
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Uninitialized action execution.
     */
    public function testExecuteActionWithoutInitialization()
    {
        $this->actionGroup->execute([]);
    }

    /**
     * @dataProvider executeActionDataProvider
     *
     * @param array $context
     * @param array $options
     * @param ActionData $arguments
     * @param $returnVal
     * @param $expected
     */
    public function testExecuteAction(
        array $context,
        array $options,
        ActionData $arguments,
        $returnVal,
        $expected
    ) {
        $data = new ActionData($context);

        $mockActionGroup = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroup')
            ->disableOriginalConstructor()
            ->getMock();

        //during initialize
        $this->mockActionGroupRegistry->expects($this->once())
            ->method('getNames')
            ->willReturn([self::ACTION_GROUP_NAME]);

        //during execute
        $this->mockActionGroupRegistry->expects($this->once())
            ->method('get')
            ->with(self::ACTION_GROUP_NAME)
            ->willReturn($mockActionGroup);

        $mockActionGroup->expects($this->once())
            ->method('execute')
            ->with($arguments)
            ->willReturn($returnVal);

        $this->actionGroup->initialize($options);
        $this->actionGroup->execute($data);

        $this->assertEquals($expected, $data);
    }

    /**
     * @return array
     */
    public function executeActionDataProvider()
    {
        $actionData = $this->createActionData(['paramValue' => 'value']);

        return [
            'without attribute and pass errors' => [
                'contextParams' => [
                    'param' => 'value',
                    'errors' => new ArrayCollection(),
                ],
                'options' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_GROUP_NAME,
                    RunActionGroup::OPTION_PARAMETERS_MAP => [
                        'paramValue' => new PropertyPath('param')
                    ]
                ],
                'arguments' => $actionData,
                'return' => new ActionData([]),
                'expected' => $this->createActionData(['param' => 'value', 'errors' => new ArrayCollection()])
            ],
            'with attributes' => [
                'contextParams' => [
                    'param' => 'value',
                ],
                'options' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_GROUP_NAME,
                    RunActionGroup::OPTION_PARAMETERS_MAP => [
                        'paramValue' => new PropertyPath('param')
                    ],
                    RunActionGroup::OPTION_RESULTS => ['result' => new PropertyPath('a')],
                ],
                'arguments' => $actionData,
                'return' => new ActionData(['a' => 'A', 'b' => ['B']]),
                'expected' => $this->createActionData(
                    [
                        'param' => 'value',
                        'result' => 'A'
                    ],
                    true
                )
            ],
            'with attribute' => [
                'contextParams' => [
                    'param' => 'value',
                ],
                'options' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_GROUP_NAME,
                    RunActionGroup::OPTION_PARAMETERS_MAP => [
                        'paramValue' => new PropertyPath('param')
                    ],
                    RunActionGroup::OPTION_RESULT => new PropertyPath('all'),
                ],
                'arguments' => $actionData,
                'return' => new ActionData(['a' => 'A', 'b' => ['B']]),
                'expected' => $this->createActionData(
                    [
                        'param' => 'value',
                        'all' => new ActionData(['a' => 'A', 'b' => ['B']])
                    ],
                    true
                )
            ]
        ];
    }

    /**
     * @param array $data
     * @param bool $modified
     * @return null|ActionData
     */
    protected function createActionData(array $data, $modified = false)
    {
        $actionData = null;

        if ($modified) {
            $actionData = new ActionData();

            foreach ($data as $name => $value) {
                $actionData->$name = $value;
            }
        } else {
            $actionData = new ActionData($data);
        }

        return $actionData;
    }
}
