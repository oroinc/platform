<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Action\RunActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;

use Oro\Component\Action\Model\ContextAccessor;

class RunActionGroupTest extends \PHPUnit_Framework_TestCase
{
    const ACTION_NAME = 'test_action';

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionGroupRegistry */
    protected $mockActionGroupRegistry;

    /** @var RunActionGroup */
    protected $function;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->mockActionGroupRegistry = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroupRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->function = new RunActionGroup($this->mockActionGroupRegistry, new ContextAccessor());
        $this->function->setDispatcher($this->eventDispatcher);
    }

    protected function tearDown()
    {
        unset($this->function, $this->eventDispatcher, $this->mockActionGroupRegistry);
    }

    public function testOptionNamesRequirements()
    {
        $this->assertEquals(RunActionGroup::OPTION_ACTION_GROUP, 'action_group');
        $this->assertEquals(RunActionGroup::OPTION_PARAMETERS_MAP, 'parameters_mapping');
        $this->assertEquals(RunActionGroup::OPTION_ATTRIBUTES, 'attributes');
        $this->assertEquals(RunActionGroup::OPTION_ATTRIBUTE, 'attribute');
    }

    public function testInitialize()
    {
        $parametersMap = [
            'entity_class' => 'testClass',
            'entity_id' => 1
        ];

        $options = [
            RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_NAME,
            RunActionGroup::OPTION_PARAMETERS_MAP => $parametersMap,
            RunActionGroup::OPTION_ATTRIBUTES => [],
            RunActionGroup::OPTION_ATTRIBUTE => new PropertyPath('path')
        ];

        $this->mockActionGroupRegistry->expects($this->once())
            ->method('getNames')
            ->willReturn([self::ACTION_NAME]);

        $this->assertInstanceOf(
            'Oro\Component\Action\Action\ActionInterface',
            $this->function->initialize($options)
        );

        $this->assertAttributeInstanceOf(
            'Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs',
            'executionArgs',
            $this->function
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
        $this->setExpectedException($exception, $exceptionMessage);

        $this->mockActionGroupRegistry->expects($this->once())->method('getNames')->willReturn([self::ACTION_NAME]);

        $this->function->initialize($inputData);
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
                    'Accepted values are: "test_action".'
            ],
            'bad parameters map type' => [
                'inputData' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_NAME,
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
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_NAME,
                    RunActionGroup::OPTION_ATTRIBUTE => '$.nonConvertedPropertyPath'
                ],
                'expectedException' => 'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                'expectedExceptionMessage' => sprintf(
                    'The option "attribute" with value "$.nonConvertedPropertyPath"' .
                    ' is expected to be of type "null" or "Symfony\Component\PropertyAccess\PropertyPathInterface"',
                    RunActionGroup::OPTION_ATTRIBUTE
                )
            ]
        ];
    }

    public function testExecuteActionWithoutInitialization()
    {
        $this->setExpectedException('\BadMethodCallException', 'Uninitialized action execution.');
        $this->function->execute([]);
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
            ->willReturn([self::ACTION_NAME]);

        //during execute
        $this->mockActionGroupRegistry->expects($this->once())
            ->method('get')
            ->with(self::ACTION_NAME)
            ->willReturn($mockActionGroup);

        $mockActionGroup->expects($this->once())
            ->method('execute')
            ->with($arguments)
            ->willReturn($returnVal);

        $this->function->initialize($options);
        $this->function->execute($data);

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
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_NAME,
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
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_NAME,
                    RunActionGroup::OPTION_PARAMETERS_MAP => [
                        'paramValue' => new PropertyPath('param')
                    ],
                    RunActionGroup::OPTION_ATTRIBUTES => ['result' => new PropertyPath('a')],
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
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_NAME,
                    RunActionGroup::OPTION_PARAMETERS_MAP => [
                        'paramValue' => new PropertyPath('param')
                    ],
                    RunActionGroup::OPTION_ATTRIBUTE => new PropertyPath('all'),
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
