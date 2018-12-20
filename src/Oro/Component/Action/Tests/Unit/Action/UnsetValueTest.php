<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\AssignValue;
use Oro\Component\Action\Action\UnsetValue;
use Symfony\Component\EventDispatcher\EventDispatcher;

class UnsetValueTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|AssignValue
     */
    protected $assignValue;

    /**
     * @var ActionInterface
     */
    protected $action;

    protected function setUp()
    {
        $this->assignValue = $this->getMockBuilder('Oro\Component\Action\Action\AssignValue')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new UnsetValue($this->assignValue);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    public function testExecute()
    {
        $context = array();
        $this->assignValue->expects($this->once())
            ->method('execute')
            ->with($context);
        $this->action->execute($context);
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     * @param array $expected
     */
    public function testInitialize(array $options, array $expected)
    {
        $this->assignValue->expects($this->once())
            ->method('initialize')
            ->with($expected);

        $this->action->initialize($options);
    }

    public function optionsDataProvider()
    {
        return array(
            array(
                array(), array('value' => null)
            ),
            array(
                array('attribute' => 'test'), array('attribute' => 'test', 'value' => null)
            ),
            array(
                array('test'), array('test', null)
            )
        );
    }

    public function testSetCondition()
    {
        $condition = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionInterface')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->assignValue->expects($this->once())
            ->method('setCondition')
            ->with($condition);

        $this->action->setCondition($condition);
    }
}
