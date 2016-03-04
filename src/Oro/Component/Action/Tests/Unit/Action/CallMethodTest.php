<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\Action\Action\CallMethod;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;

class CallMethodTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CallMethod
     */
    protected $action;

    protected function setUp()
    {
        $this->action = new CallMethod(new ContextAccessor());
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Method name parameter is required
     */
    public function testInitializeNoMethod()
    {
        $this->action->initialize(array());
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Object must be valid property definition
     */
    public function testInitializeInvalidObject()
    {
        $this->action->initialize(
            array(
                'method' => 'do',
                'object' => 'stringData'
            )
        );
    }

    public function testInitialize()
    {
        $options = array(
            'method' => 'test',
            'object' => new PropertyPath('object'),
            'method_parameters' => null,
            'attribute' => 'test'
        );
        $this->assertInstanceOf(
            'Oro\Component\Action\Action\ActionInterface',
            $this->action->initialize($options)
        );
        $this->assertAttributeEquals($options, 'options', $this->action);
    }

    public function testExecuteMethod()
    {
        $context = new ItemStub(array('key' => 'value'));
        $options = array(
            'method' => function ($a) {
                \PHPUnit_Framework_Assert::assertEquals('value', $a);
                return 'bar';
            },
            'method_parameters' => array(new PropertyPath('key')),
            'attribute' => 'test'
        );

        $this->action->initialize($options);
        $this->action->execute($context);

        $this->assertEquals(array('key' => 'value', 'test' => 'bar'), $context->getData());
    }

    public function testExecuteClassMethod()
    {
        $context = new ItemStub(array('object' => $this));
        $options = array(
            'method' => 'assertCall',
            'object' => new PropertyPath('object'),
            'method_parameters' => array('test'),
            'attribute' => 'test'
        );

        $this->action->initialize($options);
        $this->action->execute($context);

        $this->assertEquals(array('object' => $this, 'test' => 'bar'), $context->getData());
    }

    public function testExecuteClassMethodNoAssign()
    {
        $context = new ItemStub(array('object' => $this));
        $options = array(
            'method' => 'assertCall',
            'object' => new PropertyPath('object'),
            'method_parameters' => array('test')
        );

        $this->action->initialize($options);
        $this->action->execute($context);

        $this->assertEquals(array('object' => $this), $context->getData());
    }

    public function assertCall($a)
    {
        $this->assertEquals('test', $a);
        return 'bar';
    }
}
