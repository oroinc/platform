<?php
namespace Oro\Bundle\WorkflowBundle\Tests\Unit\ConfigExpression;

use Oro\Bundle\WorkflowBundle\ConfigExpression\Configurable;

class ConfigurableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $assembler;

    /**
     * @var Configurable
     */
    protected $condition;

    protected function setUp()
    {
        $this->assembler = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionAssembler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->condition = new Configurable($this->assembler);
    }
    public function testInitialize()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize(array())
        );
    }
    public function testEvaluate()
    {
        $options = [];
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $errors = $this->getMockForAbstractClass('Doctrine\Common\Collections\Collection');
        $realCondition = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionInterface')
            ->getMockForAbstractClass();
        $realCondition->expects($this->exactly(2))
            ->method('evaluate')
            ->with($workflowItem, $errors)
            ->willReturn(true);
        $this->assembler->expects($this->once())
            ->method('assemble')
            ->with($options)
            ->willReturn($realCondition);
        $this->condition->initialize($options);
        $this->assertTrue($this->condition->evaluate($workflowItem, $errors));
        $this->assertTrue($this->condition->evaluate($workflowItem, $errors));
    }
}
