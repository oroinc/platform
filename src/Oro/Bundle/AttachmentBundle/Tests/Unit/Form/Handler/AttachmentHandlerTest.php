<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Form\Handler\AttachmentHandler;

class AttachmentHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $form;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
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
        $this->form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->attachment = new Attachment();

        $this->handler = new AttachmentHandler($this->request, $this->om);
    }

    public function testNotValidForm()
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));

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
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'));

        $this->form->expects($this->never())
            ->method('submit');

        $this->form->expects($this->never())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->assertFalse($this->handler->process($this->form));
    }

    public function testGoodRequest()
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));

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
