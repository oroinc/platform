<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Form\Handler\EmailNotificationHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class EmailNotificationHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var Request */
    private $request;

    /** @var EmailNotification */
    private $entity;

    /** @var EmailNotificationHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);

        $this->manager = $this->createMock(ObjectManager::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->manager);

        $this->request = new Request();
        $this->entity = new EmailNotification();

        $this->handler = new EmailNotificationHandler($registry);
    }

    public function testProcessException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument data should be instance of EmailNotification entity');

        $this->handler->process(new \stdClass(), $this->form, $this->request);
    }

    public function testProcessUnsupportedRequest(): void
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse($this->handler->process($this->entity, $this->form, $this->request));
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(string $method, string $marker, bool $expectedHandleRequest): void
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects($expectedHandleRequest ? $this->once() : $this->never())
            ->method('submit');

        $this->request->setMethod($method);
        $this->request->request->set($marker, true);

        $this->assertFalse($this->handler->process($this->entity, $this->form, $this->request));
    }

    public function processDataProvider(): array
    {
        return [
            'put request without marker' => [
                'method' => Request::METHOD_PUT,
                'marker' => 'fake',
                'expectedHandleRequest' => true
            ],
            'post request without marker' => [
                'method' => Request::METHOD_POST,
                'marker' => 'fake',
                'expectedHandleRequest' => true
            ],
            'put request with marker' => [
                'method' => Request::METHOD_PUT,
                'marker' => EmailNotificationHandler::WITHOUT_SAVING_KEY,
                'expectedHandleRequest' => false
            ],
            'post request with marker' => [
                'method' => Request::METHOD_POST,
                'marker' => EmailNotificationHandler::WITHOUT_SAVING_KEY,
                'expectedHandleRequest' => false
            ]
        ];
    }

    public function testProcessValidData(): void
    {
        $this->form->expects($this->any())
            ->method('getName')
            ->willReturn('formName');
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->request->initialize([], [
            'formName' => self::FORM_DATA
        ]);
        $this->request->setMethod('POST');

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->entity);
        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity, $this->form, $this->request));
    }
}
