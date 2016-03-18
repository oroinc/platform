<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

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
    protected $actionGroupRegistry;

    /** @var RunActionGroup */
    protected $function;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->actionGroupRegistry = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroupRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->function = new RunActionGroup($this->actionGroupRegistry, new ContextAccessor());
        $this->function->setDispatcher($this->eventDispatcher);
    }

    protected function tearDown()
    {
        unset($this->function, $this->eventDispatcher, $this->actionGroupRegistry);
    }

    public function testOptionNamesRequirements()
    {
        $this->assertEquals(RunActionGroup::OPTION_ACTION_GROUP, 'action_group');
        $this->assertEquals(RunActionGroup::OPTION_PARAMETERS_MAP, 'parameters_mapping');
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
            RunActionGroup::OPTION_ATTRIBUTE => 'writeResultTo'
        ];

        $this->assertInstanceOf(
            'Oro\Component\Action\Action\ActionInterface',
            $this->function->initialize($options)
        );

        $this->assertAttributeInstanceOf(
            'Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs',
            'executionArgs',
            $this->function
        );
        $this->assertAttributeEquals($parametersMap, 'parametersMap', $this->function);
        $this->assertAttributeEquals('writeResultTo', 'attribute', $this->function);
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
                'expectedException' => 'Oro\Component\Action\Exception\InvalidParameterException',
                'expectedExceptionMessage' => sprintf('`%s` parameter is required', RunActionGroup::OPTION_ACTION_GROUP)
            ],
            'bad parameters map type' => [
                'inputData' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_NAME,
                    RunActionGroup::OPTION_PARAMETERS_MAP => 'string is not supported'
                ],
                'expectedException' => 'Oro\Component\Action\Exception\InvalidParameterException',
                'expectedExceptionMessage' => sprintf(
                    'Option `%s` must be array or implement \Traversable interface',
                    RunActionGroup::OPTION_PARAMETERS_MAP
                ),
                $mockGroup
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
        $this->actionGroupRegistry->expects($this->once())->method('get')
            ->with($options[RunActionGroup::OPTION_ACTION_GROUP])
            ->willReturn($mockActionGroup);

        //during execute
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
        $actionData1 = new ActionData(['data' => (object)['paramValue' => 'value']]);

        $actionDataWithAttributeApplied = new ActionData(['param' => 'value']);
        $actionDataWithAttributeApplied['new_param_data'] = 'return value';

        return [
            'without attribute' => [
                'contextParams' => ['param' => 'value'],
                'options' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_NAME,
                    RunActionGroup::OPTION_PARAMETERS_MAP => [
                        'paramValue' => new PropertyPath('param')
                    ]
                ],
                'arguments' => $actionData1,
                'return' => 'not matters',
                'expected' => new ActionData(['param' => 'value'])
            ],
            'with attribute' => [
                'contextParams' => ['param' => 'value'],
                'options' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_NAME,
                    RunActionGroup::OPTION_PARAMETERS_MAP => [
                        'paramValue' => new PropertyPath('param')
                    ],
                    RunActionGroup::OPTION_ATTRIBUTE => new PropertyPath('new_param_data')
                ],
                'arguments' => $actionData1,
                'return' => 'return value',
                'expected' => $actionDataWithAttributeApplied
            ]
        ];
    }
}
