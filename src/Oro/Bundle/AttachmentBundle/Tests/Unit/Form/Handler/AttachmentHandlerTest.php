<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Form\Handler\AttachmentHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AttachmentHandlerTest extends TestCase
{
    private FormInterface&MockObject $form;
    private Request $request;
    private ObjectManager&MockObject $om;
    private Attachment $attachment;
    private AttachmentHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->om = $this->createMock(ObjectManager::class);
        $this->attachment = new Attachment();

        $this->handler = new AttachmentHandler($requestStack, $this->om);
    }

    public function testNotValidForm(): void
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit');

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->om->expects($this->never())
            ->method('persist');

        $this->om->expects($this->never())
            ->method('flush');

        $this->assertFalse($this->handler->process($this->form));
    }

    public function testBadRequest(): void
    {
        $this->request->setMethod('GET');

        $this->form->expects($this->never())
            ->method('submit');

        $this->form->expects($this->never())
            ->method('isValid')
            ->willReturn(true);

        $this->assertFalse($this->handler->process($this->form));
    }

    public function testGoodRequest(): void
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit');

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn($this->attachment);

        $this->om->expects($this->once())
            ->method('persist')
            ->with($this->equalTo($this->attachment));
        $this->om->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->form));
    }
}
