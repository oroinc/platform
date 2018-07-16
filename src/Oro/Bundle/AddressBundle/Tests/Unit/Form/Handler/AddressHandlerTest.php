<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Form\Handler\AddressHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AddressHandlerTest extends \PHPUnit\Framework\TestCase
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
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $address;

    /**
     * @var AddressHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->om = $this->createMock(ObjectManager::class);
        $this->address = $this->createMock(Address::class);

        $this->handler = new AddressHandler($this->form, $requestStack, $this->om);
    }

    public function testGoodRequest()
    {
        $this->form->expects($this->once())
            ->method('setData');

        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit');
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue('true'));

        $this->om->expects($this->once())
            ->method('persist')
            ->with($this->equalTo($this->address));
        $this->om->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->address));
    }

    public function testBadRequest()
    {
        $this->form->expects($this->once())
            ->method('setData');

        $this->request->setMethod('GET');

        $this->form->expects($this->never())
            ->method('submit');
        $this->form->expects($this->never())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->om->expects($this->never())
            ->method('persist');
        $this->om->expects($this->never())
            ->method('flush');

        $this->assertFalse($this->handler->process($this->address));
    }

    public function testNotValidForm()
    {
        $this->form->expects($this->once())
            ->method('setData');

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

        $this->assertFalse($this->handler->process($this->address));
    }
}
