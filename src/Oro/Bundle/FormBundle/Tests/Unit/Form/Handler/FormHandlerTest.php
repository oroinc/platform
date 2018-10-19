<?php

namespace Oro\Bundle\FormBundle\Test\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\FormBundle\Form\Handler\FormHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class FormHandlerTest extends \PHPUnit\Framework\TestCase
{
    const FORM_DATA = ['field' => 'value'];

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    /** @var FormHandler */
    private $handler;

    protected function setUp()
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->request = new Request();
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->handler = new FormHandler($this->eventDispatcher, $this->doctrineHelper);
    }

    public function testHandleUpdateWorksWithInvalidForm()
    {
        $entity = (object)[];
        $this->form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');
        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $this->assertProcessEventsTriggered($this->form, $entity);
        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->assertEquals(false, $this->handler->process($entity, $this->form, $this->request));
    }

    public function testHandleUpdateWorksWithValidForm()
    {
        $entity = (object)[];
        $this->form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');
        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('persist')
            ->with($entity);
        $em->expects($this->once())
            ->method('flush');
        $em->expects($this->once())
            ->method('commit');
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($entity)
            ->will($this->returnValue($em));

        $this->assertProcessEventsTriggered($this->form, $entity);
        $this->assertProcessAfterEventsTriggered($this->form, $entity);
        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch');

        $this->assertTrue($this->handler->process($entity, $this->form, $this->request));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Test flush exception
     */
    public function testHandleUpdateWorksWhenFormFlushFailed()
    {
        $entity = (object)[];
        $this->form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');
        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('persist')
            ->with($entity);
        $em->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception('Test flush exception'));
        $em->expects($this->once())
            ->method('rollback');
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($entity)
            ->will($this->returnValue($em));

        $this->handler->process($entity, $this->form, $this->request);
    }

    public function testHandleUpdateBeforeFormDataSetInterrupted()
    {
        $entity = (object)[];
        $this->request->setMethod('POST');
        $this->form->expects($this->never())
            ->method('submit');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_DATA_SET)
            ->willReturnCallback(
                function ($name, FormProcessEvent $event) {
                    $event->interruptFormProcess();
                }
            );

        $this->assertFalse($this->handler->process($entity, $this->form, $this->request));
    }

    public function testProcessFalseNotRequiredRequestMethod()
    {
        $entity = (object)[];
        $this->request->setMethod('GET');

        $this->form->expects($this->never())
            ->method('submit');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_DATA_SET, new FormProcessEvent($this->form, $entity));

        $this->assertFalse($this->handler->process($entity, $this->form, $this->request));
    }

    public function testHandleUpdateInterruptedBeforeFormSubmit()
    {
        $entity = (object)[];
        $this->request->setMethod('POST');
        $this->form->expects($this->never())
            ->method('submit');

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_SUBMIT)
            ->willReturnCallback(
                function ($name, FormProcessEvent $event) {
                    $event->interruptFormProcess();
                }
            );

        $this->assertFalse($this->handler->process($entity, $this->form, $this->request));
    }

    /**
     * @param FormInterface $form
     * @param object $entity
     */
    protected function assertProcessEventsTriggered(FormInterface $form, $entity)
    {
        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_DATA_SET, new FormProcessEvent($form, $entity));

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_SUBMIT, new FormProcessEvent($form, $entity));
    }

    /**
     * @param FormInterface $form
     * @param object $entity
     */
    protected function assertProcessAfterEventsTriggered(FormInterface $form, $entity)
    {
        $this->eventDispatcher->expects($this->at(2))
            ->method('dispatch')
            ->with(Events::BEFORE_FLUSH, new AfterFormProcessEvent($form, $entity));

        $this->eventDispatcher->expects($this->at(3))
            ->method('dispatch')
            ->with(Events::AFTER_FLUSH, new AfterFormProcessEvent($form, $entity));
    }
}
