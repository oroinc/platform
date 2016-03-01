<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Model\Action\ActionAssembler;
use Oro\Bundle\WorkflowBundle\Model\Condition\Configurable as ConfigurableCondition;
use Oro\Bundle\WorkflowBundle\Model\Process;
use Oro\Component\ConfigExpression\ExpressionFactory;

class ProcessTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProcessDefinition $processDefinition
     */
    protected $processDefinition;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExpressionFactory $conditionFactory
     */
    protected $conditionFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ActionAssembler $actionAssembler
     */
    protected $actionAssembler;

    /**
     * @var Process
     */
    protected $process;

    protected function setUp()
    {
        $this->processDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->actionAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Action\ActionAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->process = new Process($this->actionAssembler, $this->conditionFactory, $this->processDefinition);
    }

    public function testExecute()
    {
        $context = array('context');
        $configuration = array('config');

        $action = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface')
            ->getMock();
        $action->expects($this->exactly(2))
            ->method('execute')
            ->with($context);

        $this->processDefinition->expects($this->once())
            ->method('getActionsConfiguration')
            ->will($this->returnValue($configuration));
        $this->actionAssembler->expects($this->once())
            ->method('assemble')
            ->will($this->returnValue($action));

        $this->process->execute($context);
        $this->process->execute($context);
    }

    public function testIsApplicableNoPreConditionsSection()
    {
        $this->conditionFactory->expects($this->never())
            ->method($this->anything());

        $this->assertTrue($this->process->isApplicable([]));
    }

    public function testIsApplicableNoPreConditions()
    {
        $context = [];
        $conditionConfiguration = null;

        $this->processDefinition->expects($this->once())
            ->method('getPreConditionsConfiguration')
            ->will($this->returnValue($conditionConfiguration));

        $this->conditionFactory->expects($this->never())
            ->method($this->anything());

        $this->assertTrue($this->process->isApplicable($context));
    }

    public function testIsApplicable()
    {
        $context = [];
        $conditionConfiguration = ['test' => []];
        $condition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Condition\Configurable')
            ->disableOriginalConstructor()
            ->getMock();
        $condition->expects($this->any())
            ->method('evaluate')
            ->with($context)
            ->will($this->returnValue(false));

        $this->processDefinition->expects($this->once())
            ->method('getPreConditionsConfiguration')
            ->will($this->returnValue($conditionConfiguration));

        $this->conditionFactory->expects($this->once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $conditionConfiguration)
            ->will($this->returnValue($condition));

        $this->assertFalse($this->process->isApplicable($context));
        $this->assertFalse($this->process->isApplicable($context));
    }

    public function testExecutePreConditionsAreNotMet()
    {
        $context = [];
        $conditionConfiguration = ['test' => []];
        $condition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Condition\Configurable')
            ->disableOriginalConstructor()
            ->getMock();
        $condition->expects($this->any())
            ->method('evaluate')
            ->with($context)
            ->will($this->returnValue(false));

        $this->processDefinition->expects($this->once())
            ->method('getPreConditionsConfiguration')
            ->will($this->returnValue($conditionConfiguration));

        $this->conditionFactory->expects($this->once())
            ->method('create')
            ->with(ConfigurableCondition::ALIAS, $conditionConfiguration)
            ->will($this->returnValue($condition));

        $this->processDefinition->expects($this->never())
            ->method('getActionsConfiguration');
        $this->actionAssembler->expects($this->never())
            ->method('assemble');
        $this->process->execute($context);
    }
}
