<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Extension\InlineEditing\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EntityBundle\Form\EntityField\Handler\EntityApiBaseHandler;
use Oro\Bundle\EntityBundle\Form\EntityField\Handler\Processor\EntityApiHandlerProcessor;
use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Stub\SomeEntity;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;

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

    /**
     * @var EntityClassNameHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityClassNameHelper;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->processor = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Form\EntityField\Handler\Processor\EntityApiHandlerProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassNameHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new EntityApiBaseHandler($this->registry, $this->processor, $this->entityClassNameHelper);
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

        $this->assertEquals([], $this->handler->process($entity, $form, $data, $method));
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
        $form->expects($this->once())
            ->method('submit')
            ->with($data);

        $this->assertEquals([], $this->handler->process($entity, $form, $data, $method));
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

        $this->assertEquals([], $this->handler->process($entity, $form, $data, $method));
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
      
        $this->initManager();

        $this->assertEquals([
            'fields' => [
                'a' => '1',
                'b' => '2'
            ]
        ], $this->handler->process($entity, $form, $data, $method));
    }

    protected function initManager()
    {
        $manager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('persist');
        $manager->expects($this->any())
            ->method('flush');


        $this->registry->expects($this->any())->method('getManager')->willReturn($manager);
    }
}
