<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EmailBundle\Form\Handler\EmailHandler;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
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

    private Processor|\PHPUnit\Framework\MockObject\MockObject $emailProcessor;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private EmailHandler $handler;

    private Email $model;

    protected function setUp(): void
    {
        $this->form = $this->createMock(Form::class);

        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);

        $this->emailProcessor = $this->createMock(Processor::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->model = new Email();

        $this->handler = new EmailHandler(
            $this->form,
            $requestStack,
            $this->emailProcessor,
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
                $this->emailProcessor->expects(self::once())
                    ->method('process')
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
        $this->emailProcessor->expects(self::once())
            ->method('process')
            ->with($this->model)
            ->willReturnCallback(function () use ($exception) {
                throw $exception;
            });

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Email sending failed.', ['exception' => $exception]);
        $this->form->expects(self::once())
            ->method('addError')
            ->with($this->isInstanceOf(FormError::class));

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
