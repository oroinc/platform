<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ActionBundle\Action\RunAction;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Component\ConfigExpression\Model\ContextAccessor;

class RunActionTest extends \PHPUnit_Framework_TestCase
{
    const ACTION_NAME = 'test_action';

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var RunAction */
    protected $function;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionManager */
    protected $manager;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper $contextHelper */
        $contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $contextHelper->expects($this->any())
            ->method('getActionData')
            ->willReturn(new ActionData(['data' => ['param']]));

        $this->manager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->function = new RunAction(new ContextAccessor(), $this->manager, $contextHelper);
        $this->function->setDispatcher($this->eventDispatcher);
    }

    protected function tearDown()
    {
        unset($this->function, $this->eventDispatcher, $this->manager);
    }

    public function testInitialize()
    {
        $options = [
            'action' => self::ACTION_NAME,
            'entity_class' => 'testClass',
            'entity_id' => 1,
        ];

        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Action\ActionInterface',
            $this->function->initialize($options)
        );

        $this->assertAttributeEquals($options, 'options', $this->function);
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
        return [
            [
                'inputData' => [],
                'expectedException' => 'Oro\Component\ConfigExpression\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Action name parameter is required'
            ],
            [
                'inputData' => [
                    'action' => self::ACTION_NAME
                ],
                'expectedException' => 'Oro\Component\ConfigExpression\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Entity class parameter is required',
            ],
            [
                'inputData' => [
                    'action' => self::ACTION_NAME,
                    'entity_class' => 'entityClass'
                ],
                'expectedException' => 'Oro\Component\ConfigExpression\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Entity id parameter is required',
            ]
        ];
    }

    /**
     * @dataProvider executeActionDataProvider
     *
     * @param array $params
     * @param array $options
     * @param ActionData $expected
     * @param array $result
     */
    public function testExecuteAction(array $params, array $options, ActionData $expected, array $result = [])
    {
        $this->assertManagerCalled($result);

        $data = new ActionData($params);

        $this->function->initialize($options);
        $this->function->execute($data);

        $this->assertEquals($expected, $data);
    }

    /**
     * @return array
     */
    public function executeActionDataProvider()
    {
        $actionData1 = new ActionData(['param' => 'value']);

        $actionData2 = clone $actionData1;
        $actionData2->offsetSet('new_param', new ActionData(['new_param_data' => 'value']));

        return [
            'without attribute' => [
                'params' => ['param' => 'value'],
                'options' => [
                    'action' => self::ACTION_NAME,
                    'entity_class' => 'testClass',
                    'entity_id' => 1
                ],
                'expected' => $actionData1
            ],
            'with attribute' => [
                'params' => ['param' => 'value'],
                'options' => [
                    'action' => self::ACTION_NAME,
                    'entity_class' => 'testClass',
                    'entity_id' => 1,
                    'attribute' => 'new_param'
                ],
                'expected' => $actionData2,
                'result' => ['new_param_data' => 'value']
            ]
        ];
    }

    /**
     * @param array $data
     */
    protected function assertManagerCalled(array $data)
    {
        $this->manager->expects($this->once())
            ->method('execute')
            ->willReturn(new ActionData($data));
    }
}
