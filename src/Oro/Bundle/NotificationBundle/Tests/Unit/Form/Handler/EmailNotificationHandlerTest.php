<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Form\Handler\EmailNotificationHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class EmailNotificationHandlerTest extends \PHPUnit\Framework\TestCase
{
    const FORM_DATA = ['field' => 'value'];

    /** @var FormInterface |\PHPUnit\Framework\MockObject\MockObject*/
    protected $form;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $manager;

    /** @var Request */
    protected $request;

    /** @var EmailNotification */
    protected $entity;

    /** @var EmailNotificationHandler */
    protected $handler;

    protected function setUp()
    {
        $this->form = $this->createMock(FormInterface::class);

        $this->manager = $this->createMock(ObjectManager::class);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())->method('getManagerForClass')->willReturn($this->manager);

        $this->request = new Request();
        $this->entity = new EmailNotification();

        $this->handler = new EmailNotificationHandler($registry);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Argument data should be instance of EmailNotification entity
     */
    public function testProcessException()
    {
        $this->handler->process(new \stdClass(), $this->form, $this->request);
    }

    public function testProcessUnsupportedRequest()
    {
        $this->form->expects($this->once())->method('setData')->with($this->entity);
        $this->form->expects($this->never())->method('submit');

        $this->assertFalse($this->handler->process($this->entity, $this->form, $this->request));
    }

    /**
     * @dataProvider processDataProvider
     *
     * @param string $method
     * @param string $marker
     * @param bool $expectedHandleRequest
     */
    public function testProcess($method, $marker, $expectedHandleRequest)
    {
        $this->form->expects($this->once())->method('setData')->with($this->entity);
        $this->form->expects($expectedHandleRequest ? $this->once() : $this->never())->method('submit');

        $this->request->setMethod($method);
        $this->request->request->set($marker, true);

        $this->assertFalse($this->handler->process($this->entity, $this->form, $this->request));
    }

    /**
     * @return \Generator
     */
    public function processDataProvider()
    {
        yield 'put request without marker' => [
            'method' => Request::METHOD_PUT,
            'marker' => 'fake',
            'expectedHandleRequest' => true
        ];

        yield 'post request without marker' => [
            'method' => Request::METHOD_POST,
            'marker' => 'fake',
            'expectedHandleRequest' => true
        ];

        yield 'put request with marker' => [
            'method' => Request::METHOD_PUT,
            'marker' => EmailNotificationHandler::WITHOUT_SAVING_KEY,
            'expectedHandleRequest' => false
        ];

        yield 'post request with marker' => [
            'method' => Request::METHOD_POST,
            'marker' => EmailNotificationHandler::WITHOUT_SAVING_KEY,
            'expectedHandleRequest' => false
        ];
    }

    public function testProcessValidData()
    {
        $this->form->expects($this->any())->method('getName')->willReturn('formName');
        $this->form->expects($this->once())->method('setData')->with($this->entity);
        $this->form->expects($this->once())->method('submit')->with(self::FORM_DATA);
        $this->form->expects($this->once())->method('isValid')->willReturn(true);

        $this->request->initialize([], [
            'formName' => self::FORM_DATA
        ]);
        $this->request->setMethod('POST');

        $this->manager->expects($this->once())->method('persist')->with($this->entity);
        $this->manager->expects($this->once())->method('flush');

        $this->assertTrue($this->handler->process($this->entity, $this->form, $this->request));
    }
}
