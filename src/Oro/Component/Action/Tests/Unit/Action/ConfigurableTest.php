<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionAssembler;
use Oro\Component\Action\Action\Configurable;

class ConfigurableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Configurable
     */
    protected $configurableAction;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ActionAssembler
     */
    protected $assembler;

    /**
     * @var array
     */
    protected $testConfiguration = array('key' => 'value');

    /**
     * @var array
     */
    protected $testContext = array(1, 2, 3);

    protected function setUp()
    {
        $this->assembler = $this->getMockBuilder('Oro\Component\Action\Action\ActionAssembler')
            ->disableOriginalConstructor()
            ->setMethods(array('assemble'))
            ->getMock();
        $this->configurableAction = new Configurable($this->assembler);
    }

    protected function tearDown()
    {
        unset($this->configurableAction, $this->assembler);
    }

    public function testInitialize()
    {
        $this->assertAttributeEmpty('configuration', $this->configurableAction);
        $this->configurableAction->initialize($this->testConfiguration);
        $this->assertAttributeEquals($this->testConfiguration, 'configuration', $this->configurableAction);
    }

    public function testExecute()
    {
        $action = $this->getMockBuilder('Oro\Component\Action\Action\ActionInterface')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $action->expects($this->exactly(2))
            ->method('execute')
            ->with($this->testContext);

        $condition = $this->getMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $condition->expects($this->never())
            ->method('evaluate');

        $this->assembler->expects($this->once())
            ->method('assemble')
            ->with($this->testConfiguration)
            ->will($this->returnValue($action));

        $this->configurableAction->initialize($this->testConfiguration);
        $this->configurableAction->setCondition($condition);

        // run twice to test cached action
        $this->configurableAction->execute($this->testContext);
        $this->configurableAction->execute($this->testContext);
    }
}
