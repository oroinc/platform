<?php

namespace Oro\Bundle\DatagridBundle\Tests\Unit\Extension\InlineEditing\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Handler\EntityApiBaseHandler;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Processor\EntityApiHandlerProcessor;
use Oro\Bundle\DatagridBundle\Tests\Unit\Stub\SomeEntity;

class EntityApiBaseHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityApiHandlerProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processor;

    /**
     * @var EntityApiBaseHandler
     */
    protected $handler;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->processor = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\InlineEditing\Processor\EntityApiHandlerProcessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new EntityApiBaseHandler($this->registry, $this->processor);
    }

    public function testProcessUnsupportedMethod()
    {
        $entity = new SomeEntity();
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $data = ['a' => 1];
        $method = 'UNSUP';

        $this->processor
            ->expects($this->once())
            ->method('preProcess')
            ->with($entity);
        $form
            ->expects($this->once())
            ->method('setData')
            ->with($entity);
        $form->expects($this->never())
            ->method('submit')
            ->with($data);

        $this->assertFalse($this->handler->process($entity, $form, $data, $method));
    }

    public function testProcessDataEmpty()
    {
        $entity = new SomeEntity();
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $data = [];
        $method = 'PATCH';

        $this->processor
            ->expects($this->once())
            ->method('preProcess')
            ->with($entity);
        $form
            ->expects($this->once())
            ->method('setData')
            ->with($entity);
        $form->expects($this->never())
            ->method('submit')
            ->with($data);

        $this->assertFalse($this->handler->process($entity, $form, $data, $method));
    }

    public function testProcessInvalid()
    {
        $entity = new SomeEntity();
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $data = ['a' => '1', 'b' => '2'];
        $method = 'PATCH';

        $this->processor
            ->expects($this->once())
            ->method('preProcess')
            ->with($entity);
        $form
            ->expects($this->once())
            ->method('setData')
            ->with($entity);
        $form->expects($this->once())
            ->method('submit')
            ->with($data);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $this->processor
            ->expects($this->never())
            ->method('beforeProcess')
            ->with($entity);
        $this->processor
            ->expects($this->never())
            ->method('afterProcess')
            ->with($entity);
        $this->processor
            ->expects($this->once())
            ->method('invalidateProcess')
            ->with($entity);

        $this->registry
            ->expects($this->never())
            ->method('getManager');

        $this->assertFalse($this->handler->process($entity, $form, $data, $method));
    }

    public function testProcessValid()
    {
        $entity = new SomeEntity();
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $data = ['a' => '1', 'b' => '2'];
        $method = 'PATCH';

        $this->processor
            ->expects($this->once())
            ->method('preProcess')
            ->with($entity);
        $form
            ->expects($this->once())
            ->method('setData')
            ->with($entity);
        $form->expects($this->once())
            ->method('submit')
            ->with($data);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->processor
            ->expects($this->once())
            ->method('beforeProcess')
            ->with($entity);
        $this->processor
            ->expects($this->once())
            ->method('afterProcess')
            ->with($entity);
        $this->processor
            ->expects($this->never())
            ->method('invalidateProcess')
            ->with($entity);

        $om = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry
            ->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($om));
        $om->expects($this->once())
            ->method('persist');
        $om->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($entity, $form, $data, $method));
    }
}
