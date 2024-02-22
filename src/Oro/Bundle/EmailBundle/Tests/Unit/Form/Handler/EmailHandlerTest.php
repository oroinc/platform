<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
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

    /** @var Form|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var EmailModelSender|\PHPUnit\Framework\MockObject\MockObject */
    private $emailModelSender;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var EmailHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->form = $this->createMock(Form::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->emailModelSender = $this->createMock(EmailModelSender::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new EmailHandler(
            $this->form,
            $this->requestStack,
            $this->emailModelSender,
            $this->logger
        );
    }

    public function testProcessGetRequest(): void
    {
        $request = new Request();
        $request->setMethod('GET');

        $model = new Email();

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->form->expects(self::once())
            ->method('setData')
            ->with($model);

        $this->form->expects(self::never())
            ->method('submit');

        self::assertFalse($this->handler->process($model));
    }

    public function testProcessPostRequestWithInitParam(): void
    {
        $request = new Request();
        $request->initialize([], self::FORM_DATA);
        $request->setMethod('POST');
        $request->request->set('_widgetInit', true);

        $model = new Email();

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->form->expects(self::once())
            ->method('setData')
            ->with($model);

        $this->form->expects(self::never())
            ->method('submit');

        self::assertFalse($this->handler->process($model));
    }

    /**
     * @dataProvider processData
     */
    public function testProcessData(string $method, bool $valid, bool $assert): void
    {
        $request = new Request();
        $request->initialize([], self::FORM_DATA);
        $request->setMethod($method);

        $model = new Email();
        $model
            ->setFrom('from@example.com')
            ->setTo(['to@example.com'])
            ->setSubject('testSubject')
            ->setBody('testBody');

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->form->expects(self::once())
            ->method('setData')
            ->with($model);

        if (in_array($method, ['POST', 'PUT'], true)) {
            $this->form->expects(self::once())
                ->method('submit')
                ->with(self::FORM_DATA);

            $this->form->expects(self::once())
                ->method('isValid')
                ->willReturn($valid);

            if ($valid) {
                $this->emailModelSender->expects(self::once())
                    ->method('send')
                    ->with(self::identicalTo($model))
                    ->willReturnCallback(function (Email $model) {
                        self::assertFalse($model->isUpdateEmptyContextsAllowed());

                        return new EmailUser();
                    });
            } else {
                $this->emailModelSender->expects(self::never())
                    ->method('send');
            }
        }

        self::assertEquals($assert, $this->handler->process($model));
    }

    /**
     * @dataProvider methodsData
     */
    public function testProcessException(string $method): void
    {
        $request = new Request();
        $request->initialize([], self::FORM_DATA);
        $request->setMethod($method);

        $model = new Email();
        $model
            ->setFrom('from@example.com')
            ->setTo(['to@example.com'])
            ->setSubject('testSubject')
            ->setBody('testBody');

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->form->expects(self::once())
            ->method('setData')
            ->with($model);

        $this->form->expects(self::once())
            ->method('submit')
            ->with(self::FORM_DATA);

        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $exception = new \Exception('TEST');
        $this->emailModelSender->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($model))
            ->willReturnCallback(function () use ($exception) {
                throw $exception;
            });

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to send email model to to@example.com: TEST',
                ['exception' => $exception, 'emailModel' => $model]
            );
        $this->form->expects(self::once())
            ->method('addError')
            ->with(self::isInstanceOf(FormError::class));

        self::assertFalse($this->handler->process($model));
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
