<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Oro\Bundle\WorkflowBundle\Model\Action\FormatName;

class FormatNameTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormatName
     */
    protected $action;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formatter;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ContextAccessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action = new FormatName($this->contextAccessor, $this->formatter);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Object parameter is required
     */
    public function testInitializeExceptionNoObject()
    {
        $this->action->initialize(array('attribute' => $this->getPropertyPath()));
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Attribute name parameter is required
     */
    public function testInitializeExceptionNoAttribute()
    {
        $this->action->initialize(array('object' => new \stdClass()));
    }

    public function testInitialize()
    {
        $options = array('object' => new \stdClass(), 'attribute' => $this->getPropertyPath());
        $this->assertEquals($this->action, $this->action->initialize($options));
        $this->assertAttributeEquals($options, 'options', $this->action);
    }

    public function testExecute()
    {
        $object = new \stdClass();
        $attribute = $this->getPropertyPath();
        $context = array();
        $options = array('object' => $object, 'attribute' => $attribute);
        $this->assertEquals($this->action, $this->action->initialize($options));
        $this->formatter->expects($this->once())
            ->method('format')
            ->with($object)
            ->will($this->returnValue('FORMATTED'));
        $this->contextAccessor->expects($this->once())
            ->method('setValue')
            ->with($context, $attribute, 'FORMATTED');
        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->with($context, $object)
            ->will($this->returnArgument(1));
        $this->action->execute($context);
    }

    protected function getPropertyPath()
    {
        return $this->getMockBuilder('Symfony\Component\PropertyAccess\PropertyPath')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
