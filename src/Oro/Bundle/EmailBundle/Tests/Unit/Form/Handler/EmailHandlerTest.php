<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EmailBundle\Form\Handler\EmailHandler;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    private Form|\PHPUnit\Framework\MockObject\MockObject $form;

    private Request $request;

    private EmailModelSender|\PHPUnit\Framework\MockObject\MockObject $emailModelSender;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private EmailHandler $handler;

    private Email $model;

    protected function setUp(): void
    {
        $this->form = $this->createMock(Form::class);

        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);

        $this->emailModelSender = $this->createMock(EmailModelSender::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->model = new Email();

        $this->handler = new EmailHandler(
            $this->form,
            $requestStack,
            $this->emailModelSender,
            $this->logger
        );
    }

    public function testProcessGetRequest(): void
    {
        $this->request->setMethod('GET');

        $this->form->expects(self::once())
            ->method('setData')
            ->with($this->model);

        $this->form->expects(self::never())
            ->method('submit');

        self::assertFalse($this->handler->process($this->model));
    }

    public function testProcessPostRequestWithInitParam(): void
    {
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');
        $this->request->request->set('_widgetInit', true);

        $this->form->expects(self::once())
            ->method('setData')
            ->with($this->model);

        $this->form->expects(self::never())
            ->method('submit');

        self::assertFalse($this->handler->process($this->model));
    }

    /**
     * @dataProvider processData
     */
    public function testProcessData(string $method, bool $valid, bool $assert): void
    {
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);
        $this->model
            ->setFrom('from@example.com')
            ->setTo(['to@example.com'])
            ->setSubject('testSubject')
            ->setBody('testBody');

        $this->form->expects(self::once())
            ->method('setData')
            ->with($this->model);

        if (in_array($method, ['POST', 'PUT'])) {
            $this->form->expects(self::once())
                ->method('submit')
                ->with(self::FORM_DATA);

            $this->form->expects(self::once())
                ->method('isValid')
                ->willReturn($valid);

            if ($valid) {
                $this->emailModelSender->expects(self::once())
                    ->method('send')
                    ->with($this->model);
            }
        }

        self::assertEquals($assert, $this->handler->process($this->model));
    }

    /**
     * @dataProvider methodsData
     */
    public function testProcessException(string $method): void
    {
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);
        $this->model
            ->setFrom('from@example.com')
            ->setTo(['to@example.com'])
            ->setSubject('testSubject')
            ->setBody('testBody');

        $this->form->expects(self::once())
            ->method('setData')
            ->with($this->model);

        $this->form->expects(self::once())
            ->method('submit')
            ->with(self::FORM_DATA);

        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $exception = new \Exception('TEST');
        $this->emailModelSender->expects(self::once())
            ->method('send')
            ->with($this->model)
            ->willReturnCallback(function () use ($exception) {
                throw $exception;
            });

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to send email model to to@example.com: TEST',
                ['exception' => $exception, 'emailModel' => $this->model]
            );
        $this->form->expects(self::once())
            ->method('addError')
            ->with(self::isInstanceOf(FormError::class));

        self::assertFalse($this->handler->process($this->model));
    }

    public function processData(): array
    {
        return [
            ['POST', true, true],
            ['POST', false, false],
            ['PUT', true, true],
            ['PUT', false, false],
            ['GET', true, false],
            ['GET', false, false],
            ['DELETE', true, false],
            ['DELETE', false, false]
        ];
    }

    public function methodsData(): array
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }
}
