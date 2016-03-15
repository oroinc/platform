<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Component\Layout\ArrayCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ActionBundle\Action\RunActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionData;

use Oro\Component\Action\Model\ContextAccessor;

class RunActionGroupTest extends \PHPUnit_Framework_TestCase
{
    const ACTION_NAME = 'test_action';

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var RunActionGroup */
    protected $function;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionGroupRegistry */
    protected $actionGroupRegistry;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->actionGroupRegistry = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroupRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->function = new RunActionGroup(new ContextAccessor(), $this->actionGroupRegistry);
        $this->function->setDispatcher($this->eventDispatcher);
    }

    protected function tearDown()
    {
        unset($this->function, $this->eventDispatcher, $this->actionGroupRegistry);
    }

    public function testOptionNamesRequirements()
    {
        $this->assertEquals(RunActionGroup::OPTION_ACTION_GROUP, 'action_group');
        $this->assertEquals(RunActionGroup::OPTION_PARAMETERS, 'parameters_mapping');
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
            RunActionGroup::OPTION_PARAMETERS => $parametersMap,
            RunActionGroup::OPTION_ATTRIBUTE => 'writeResultTo'
        ];

        $mockGroup = $this->getMockBuilder('\Oro\Bundle\ActionBundle\Model\ActionGroup')
            ->disableOriginalConstructor()
            ->getMock();

        $this->actionGroupRegistry->expects($this->once())
            ->method('findByName')
            ->with(self::ACTION_NAME)
            ->willReturn($mockGroup);

        $this->assertInstanceOf(
            'Oro\Component\Action\Action\ActionInterface',
            $this->function->initialize($options)
        );

        $this->assertAttributeEquals($mockGroup, 'actionGroup', $this->function);
        $this->assertAttributeEquals($parametersMap, 'parameters', $this->function);
        $this->assertAttributeEquals('writeResultTo', 'attribute', $this->function);
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     *
     * @param array $inputData
     * @param string $exception
     * @param string $exceptionMessage
     * @param mixed $actionGroup
     * @throws \Oro\Component\Action\Exception\InvalidParameterException
     */
    public function testInitializeException(array $inputData, $exception, $exceptionMessage, $actionGroup = false)
    {
        if ($actionGroup !== false) {
            $this->actionGroupRegistry->expects($this->once())
                ->method('findByName')
                ->with(self::ACTION_NAME)
                ->willReturn($actionGroup);
        }

        $this->setExpectedException($exception, $exceptionMessage);

        $this->function->initialize($inputData);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
    {
        $mockGroup = $this->getMockBuilder('\Oro\Bundle\ActionBundle\Model\ActionGroup')
            ->disableOriginalConstructor()
            ->getMock();

        return [
            'no action group name' => [
                'inputData' => [],
                'expectedException' => 'Oro\Component\Action\Exception\InvalidParameterException',
                'expectedExceptionMessage' => sprintf('`%s` parameter is required', RunActionGroup::OPTION_ACTION_GROUP)
            ],
            'action group not found' => [
                'inputData' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_NAME
                ],
                'expectedException' => '\RuntimeException',
                'expectedExceptionMessage' => sprintf('ActionGroup with name `%s` not found', self::ACTION_NAME)
            ],
            'bad parameters map type' => [
                'inputData' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_NAME,
                    RunActionGroup::OPTION_PARAMETERS => 'string is not supported'
                ],
                'expectedException' => 'Oro\Component\Action\Exception\InvalidParameterException',
                'expectedExceptionMessage' => sprintf(
                    'Option `%s` must be array or implement \Traversable interface',
                    RunActionGroup::OPTION_PARAMETERS
                ),
                $mockGroup
            ]
        ];
    }

    public function testExecuteActionWithoutInitialization()
    {
        $this->setExpectedException('\BadMethodCallException', 'Uninitialized execution.');
        $this->function->execute([]);
    }

    /**
     * @dataProvider executeActionDataProvider
     *
     * @param array $context
     * @param array $options
     * @param ActionGroup|\PHPUnit_Framework_MockObject_MockObject $actionGroup
     * @param ActionData $arguments
     * @param array $result
     * @throws \Oro\Component\Action\Exception\InvalidParameterException
     */
    public function testExecuteAction(
        array $context,
        array $options,
        ActionGroup $actionGroup,
        ActionData $arguments,
        $returnVal,
        $expected
    ) {
        $data = new ActionData($context);

        //during initialize
        $this->actionGroupRegistry->expects($this->once())->method('findByName')
            ->with($options[RunActionGroup::OPTION_ACTION_GROUP])
            ->willReturn($actionGroup);

        //during execute
        $mockActionGroupDefinition = $this->getMockBuilder('\Oro\Bundle\ActionBundle\Model\ActionGroupDefinition')
            ->getMock();

        $mockActionGroupDefinition->expects($this->once())
            ->method('getName')
            ->willReturn($options[RunActionGroup::OPTION_ACTION_GROUP]);

        $actionGroup->expects($this->once())
            ->method('getDefinition')
            ->willReturn($mockActionGroupDefinition);

        $actionGroup->expects($this->once())
            ->method('execute')
            ->with($arguments, new ArrayCollection())
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
        $actionData1 = new ActionData(['data' => (object)['paramValue' => 'value']]);

        $mockActionGroup = $this->getMockBuilder('\Oro\Bundle\ActionBundle\Model\ActionGroup')
            ->disableOriginalConstructor()
            ->getMock();

        return [
            'without attribute' => [
                'contextParams' => ['param' => 'value'],
                'options' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_NAME,
                    RunActionGroup::OPTION_PARAMETERS => [
                        'paramValue' => '$.param'
                    ]
                ],
                'actionGroup' => $mockActionGroup,
                'arguments' => $actionData1,
                'return' => 'not matters',
                'expected' => new ActionData(['param' => 'value'])
            ],
            'with attribute' => [
                'contextParams' => ['param' => 'value'],
                'options' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_NAME,
                    RunActionGroup::OPTION_PARAMETERS => [
                        'paramValue' => '$.param'
                    ],
                    RunActionGroup::OPTION_ATTRIBUTE => 'new_param_data'
                ],
                'actionGroup' => $mockActionGroup,
                'arguments' => $actionData1,
                'return' => 'return value',
                'expected' => new ActionData(['param' => 'value', 'new_param_data' => 'return value'])
            ]
        ];
    }

    /**
     * @param array $data
     */
    protected function assertManagerCalled(array $data)
    {
        $this->actionGroupRegistry->expects($this->once())
            ->method('find')
            ->willReturn(new ActionData($data));
    }
}
