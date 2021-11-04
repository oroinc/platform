<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\FormBundle\Form\Handler\FormHandler;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class FormHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

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

    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->request = new Request();
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->handler = new FormHandler($this->eventDispatcher, $this->doctrineHelper);
    }

    public function testHandleUpdateWorksWithInvalidForm(): void
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
            ->willReturn(false);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [new FormProcessEvent($this->form, $entity), Events::BEFORE_FORM_DATA_SET],
                [new FormProcessEvent($this->form, $entity), Events::BEFORE_FORM_SUBMIT]
            );

        $this->assertEquals(false, $this->handler->process($entity, $this->form, $this->request));
    }

    public function testHandleUpdateWorksWithValidForm(): void
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
            ->willReturn(true);

        $em = $this->createMock(EntityManager::class);
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
            ->willReturn($em);

        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [new FormProcessEvent($this->form, $entity), Events::BEFORE_FORM_DATA_SET],
                [new FormProcessEvent($this->form, $entity), Events::BEFORE_FORM_SUBMIT],
                [new AfterFormProcessEvent($this->form, $entity), Events::BEFORE_FLUSH],
                [new AfterFormProcessEvent($this->form, $entity), Events::AFTER_FLUSH]
            );

        $this->assertTrue($this->handler->process($entity, $this->form, $this->request));
    }

    public function testHandleUpdateWorksWhenFormFlushFailed(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test flush exception');

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
            ->willReturn(true);

        $em = $this->createMock(EntityManager::class);
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
            ->willReturn($em);

        $this->handler->process($entity, $this->form, $this->request);
    }

    public function testHandleUpdateBeforeFormDataSetInterrupted(): void
    {
        $entity = (object)[];
        $this->request->setMethod('POST');
        $this->form->expects($this->never())
            ->method('submit');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->anything(), Events::BEFORE_FORM_DATA_SET)
            ->willReturnCallback(function (FormProcessEvent $event) {
                $event->interruptFormProcess();

                return $event;
            });

        $this->assertFalse($this->handler->process($entity, $this->form, $this->request));
    }

    public function testProcessFalseNotRequiredRequestMethod(): void
    {
        $entity = (object)[];
        $this->request->setMethod('GET');

        $this->form->expects($this->never())
            ->method('submit');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new FormProcessEvent($this->form, $entity), Events::BEFORE_FORM_DATA_SET);

        $this->assertFalse($this->handler->process($entity, $this->form, $this->request));
    }

    public function testHandleUpdateInterruptedBeforeFormSubmit(): void
    {
        $entity = (object)[];
        $this->request->setMethod('POST');
        $this->form->expects($this->never())
            ->method('submit');

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->anything(), Events::BEFORE_FORM_DATA_SET],
                [$this->anything(), Events::BEFORE_FORM_SUBMIT]
            )
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (FormProcessEvent $event) {
                    return $event;
                }),
                new ReturnCallback(function (FormProcessEvent $event) {
                    $event->interruptFormProcess();

                    return $event;
                })
            );

        $this->assertFalse($this->handler->process($entity, $this->form, $this->request));
    }
}
