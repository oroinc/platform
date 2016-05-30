<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use Oro\Bundle\ActionBundle\Model\Parameter;
use Oro\Bundle\ActionBundle\Model\Assembler\ParameterAssembler;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\Configurable as ConfigurableAction;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory;

class ActionGroupTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionFactory */
    protected $actionFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ExpressionFactory */
    protected $conditionFactory;

    /** @var ActionGroup */
    protected $actionGroup;

    /** @var ActionData */
    protected $data;

    protected function setUp()
    {
        $this->actionFactory = $this->getMockBuilder('Oro\Component\Action\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ParametersResolver $parametersResolver */
        $parametersResolver = $this->getMockBuilder(
            'Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersResolver'
        )->disableOriginalConstructor()->getMock();

        $this->actionGroup = new ActionGroup(
            $this->actionFactory,
            $this->conditionFactory,
            new ParameterAssembler(),
            $parametersResolver,
            new ActionGroupDefinition()
        );

        $this->data = new ActionData();
    }

    protected function tearDown()
    {
        unset($this->actionGroup, $this->actionFactory, $this->conditionFactory, $this->data);
    }

    /**
     * @param ActionData $data
     * @param ActionInterface $action
     * @param ConfigurableCondition $condition
     * @param string $actionGroupName
     * @param string $exceptionMessage
     *
     * @dataProvider executeProvider
     */
    public function testExecute(
        ActionData $data,
        ActionInterface $action,
        ConfigurableCondition $condition,
        $actionGroupName,
        $exceptionMessage = ''
    ) {
        $this->actionGroup->getDefinition()->setName($actionGroupName);
        $this->actionGroup->getDefinition()->setActions(['action1']);
        $this->actionGroup->getDefinition()->setConditions(['condition1']);

        $this->actionFactory->expects($this->any())
            ->method('create')
            ->with(ConfigurableAction::ALIAS)
            ->willReturn($action);

        $this->conditionFactory->expects($this->any())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS)
            ->willReturn($condition);

        $errors = new ArrayCollection();

        if ($exceptionMessage) {
            $this->setExpectedException(
                'Oro\Bundle\ActionBundle\Exception\ForbiddenActionGroupException',
                $exceptionMessage
            );
        }

        $this->assertSame($data, $this->actionGroup->execute($data, $errors));

        $this->assertEmpty($errors->toArray());
    }

    /**
     * @return array
     */
    public function executeProvider()
    {
        $data = new ActionData(['data' => new \stdClass()]);

        return [
            '!isConditionAllowed' => [
                'data' => $data,
                'action' => $this->createActionGroup($this->never(), $data),
                'condition' => $this->createCondition($this->exactly(1), $data, false),
                'actionGroupName' => 'TestName2',
                'exception' => 'ActionGroup "TestName2" is not allowed'
            ],
            'isAllowed' => [
                'data' => $data,
                'action' => $this->createActionGroup($this->once(), $data),
                'condition' => $this->createCondition($this->exactly(1), $data, true),
                'actionGroupName' => 'TestName3',
            ],
        ];
    }

    /**
     *
     * @dataProvider isAllowedProvider
     *
     * @param ActionData $data
     * @param ConfigurableCondition $condition
     * @param bool $allowed
     */
    public function testIsAllowed(ActionData $data, $condition, $allowed)
    {
        if ($condition) {
            $this->actionGroup->getDefinition()->setConditions(['condition1']);
        }

        $this->conditionFactory->expects($condition ? $this->once() : $this->never())
            ->method('create')
            ->willReturn($condition);

        $this->assertEquals($allowed, $this->actionGroup->isAllowed($data));
    }

    /**
     * @return array
     */
    public function isAllowedProvider()
    {
        $data = new ActionData(['data' => 'value']);

        return [
            'no conditions' => [
                'data' => $data,
                'condition' => null,
                'allowed' => true,
            ],
            '!isConditionAllowed' => [
                'data' => $data,
                'condition' => $this->createCondition($this->once(), $data, false),
                'allowed' => false,
            ],
            'allowed' => [
                'data' => $data,
                'condition' => $this->createCondition($this->once(), $data, true),
                'allowed' => true,
            ],
        ];
    }

    /**
     *
     * @dataProvider getParametersProvider
     * @param array $config
     * @param Parameter[] $expected
     */
    public function testGetParameters(array $config, array $expected)
    {
        if ($config) {
            $this->actionGroup->getDefinition()->setParameters($config);
        }

        $this->assertEquals($expected, $this->actionGroup->getParameters());
    }

    /**
     * @return array
     */
    public function getParametersProvider()
    {
        $parameter1 = new Parameter('parameter1');

        return [
            'no parameters' => [
                'config' => [],
                'expected' => [],
            ],
            '1 parameter' => [
                'config' => ['parameter1' => []],
                'expected' => ['parameter1' => $parameter1],
            ],
        ];
    }

    /**
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects
     * @param ActionData $data
     * @return ActionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createActionGroup(\PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects, ActionData $data)
    {
        /* @var $action ActionInterface|\PHPUnit_Framework_MockObject_MockObject */
        $action = $this->getMock('Oro\Component\Action\Action\ActionInterface');
        $action->expects($expects)->method('execute')->with($data);

        return $action;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects
     * @param ActionData $data
     * @param bool $returnValue
     * @return ConfigurableCondition|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createCondition(
        \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects,
        ActionData $data,
        $returnValue
    ) {
        /* @var $condition ConfigurableCondition|\PHPUnit_Framework_MockObject_MockObject */
        $condition = $this->getMockBuilder('Oro\Component\Action\Condition\Configurable')
            ->disableOriginalConstructor()
            ->getMock();
        $condition->expects($expects)->method('evaluate')->with($data)->willReturn($returnValue);

        return $condition;
    }
}
