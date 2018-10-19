<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Form\Handler\AttachmentHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AttachmentHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $form;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $om;

    /**
     * @var Attachment
     */
    private $attachment;

    /**
     * @var AttachmentHandler
     */
    private $handler;

    public function setUp()
    {
        $this->form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->om = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
        $this->attachment = new Attachment();

        $this->handler = new AttachmentHandler($requestStack, $this->om);
    }

    public function testNotValidForm()
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit');

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $this->om->expects($this->never())
            ->method('persist');

        $this->om->expects($this->never())
            ->method('flush');

        $this->assertFalse($this->handler->process($this->form));
    }

    public function testBadRequest()
    {
        $this->request->setMethod('GET');

        $this->form->expects($this->never())
            ->method('submit');

        $this->form->expects($this->never())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->assertFalse($this->handler->process($this->form));
    }

    public function testGoodRequest()
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit');

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($this->attachment));

        $this->om->expects($this->once())
            ->method('persist')
            ->with($this->equalTo($this->attachment));
        $this->om->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->form));
    }
}
