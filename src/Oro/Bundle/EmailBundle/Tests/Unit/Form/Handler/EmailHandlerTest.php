<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Handler\EmailHandler;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Type\EmailType;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class EmailHandlerTest extends TestCase
{
    private FormFactoryInterface&MockObject $formFactory;
    private EmailModelSender&MockObject $emailModelSender;
    private LoggerInterface&MockObject $logger;
    private EmailHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->emailModelSender = $this->createMock(EmailModelSender::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new EmailHandler(
            $this->formFactory,
            $this->emailModelSender,
            $this->logger
        );
    }

    public function testCreateForm(): void
    {
        $options = ['sample_key' => 'sample_value'];
        $emailModel = new EmailModel();
        $form = $this->createMock(FormInterface::class);
        $this->formFactory->expects(self::once())
            ->method('createNamed')
            ->with('oro_email_email', EmailType::class, $emailModel, $options)
            ->willReturn($form);

        self::assertSame($form, $this->handler->createForm($emailModel, $options));
    }

    public function testHandleRequestWhenNotSupportedMethod(): void
    {
        $request = new Request();
        $request->setMethod('GET');

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::never())
            ->method('submit');

        $this->handler->handleRequest($form, $request);
    }

    public function testHandleRequestWhenSupportedMethodAndIsWidgetInit(): void
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->request->set('_widgetInit', true);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::never())
            ->method('submit');

        $this->handler->handleRequest($form, $request);
    }

    /**
     * @dataProvider methodsDataProvider
     */
    public function testHandleRequestWhenSupportedMethod(string $method): void
    {
        $request = new Request();
        $data = ['oro_email_email' => ['field' => 'value']];
        $request->initialize([], $data);
        $request->setMethod($method);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn('oro_email_email');
        $form->expects(self::once())
            ->method('submit')
            ->with($data['oro_email_email']);

        $this->handler->handleRequest($form, $request);
    }

    public function methodsDataProvider(): array
    {
        return [
            ['POST'],
            ['PUT'],
        ];
    }

    public function testHandleFormSubmitWhenNotSubmitted(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(false);

        self::assertFalse($this->handler->handleFormSubmit($form));
    }

    public function testHandleFormSubmitWhenSubmittedAndNotValid(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        self::assertFalse($this->handler->handleFormSubmit($form));
    }

    public function testHandleFormSubmitWhenSubmittedAndValid(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $emailModel = new EmailModel();
        $emailModel
            ->setFrom('from@example.com')
            ->setTo(['to@example.com'])
            ->setSubject('testSubject')
            ->setBody('testBody');

        $form->expects(self::once())
            ->method('getData')
            ->willReturn($emailModel);

        $this->emailModelSender->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($emailModel))
            ->willReturnCallback(static function (EmailModel $model) {
                self::assertFalse($model->isUpdateEmptyContextsAllowed());

                return new EmailUser();
            });

        self::assertTrue($this->handler->handleFormSubmit($form));
    }

    public function testHandleFormSubmitWhenException(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $emailModel = new EmailModel();
        $emailModel
            ->setFrom('from@example.com')
            ->setTo(['to@example.com'])
            ->setSubject('testSubject')
            ->setBody('testBody');

        $form->expects(self::once())
            ->method('getData')
            ->willReturn($emailModel);

        $exception = new \Exception('Sample error');
        $this->emailModelSender->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($emailModel))
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to send email model to {email_addresses}: {message}',
                [
                    'email_addresses' => implode(', ', $emailModel->getTo()),
                    'message' => $exception->getMessage(),
                    'email_model' => $emailModel,
                    'exception' => $exception,
                ]
            );

        self::assertFalse($this->handler->handleFormSubmit($form));
    }
}
