<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sender;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Factory\EmailModelFromEmailTemplateFactory;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use Oro\Bundle\EmailBundle\Sender\EmailTemplateSender;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailTemplateSenderTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private EmailModelFromEmailTemplateFactory|MockObject $emailModelFromEmailTemplateFactory;

    private EmailModelSender|MockObject $emailModelSender;

    private EmailTemplateSender $sender;

    #[\Override]
    protected function setUp(): void
    {
        $this->emailModelFromEmailTemplateFactory = $this->createMock(EmailModelFromEmailTemplateFactory::class);
        $this->emailModelSender = $this->createMock(EmailModelSender::class);

        $this->sender = new EmailTemplateSender($this->emailModelFromEmailTemplateFactory, $this->emailModelSender);

        $this->setUpLoggerMock($this->sender);
    }

    public function testSendEmailTemplateWhenException(): void
    {
        $from = From::emailAddress('no-reply@example.com');
        $recipient = (new UserStub(42))
            ->setEmail('recipient@example.com');
        $templateName = 'sample_template';
        $templateParams = ['sample_key' => 'sample_value'];

        $emailModel = new EmailModel();
        $this->emailModelFromEmailTemplateFactory
            ->expects(self::once())
            ->method('createEmailModel')
            ->with($from, $recipient, $templateName, $templateParams)
            ->willReturn($emailModel);

        $exception = new \Exception('sample error');
        $this->emailModelSender
            ->expects(self::once())
            ->method('send')
            ->with($emailModel, $emailModel->getOrigin())
            ->willThrowException($exception);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to send an email to {recipients_emails} using "{template_name}" email template: {message}',
                [
                    'exception' => $exception,
                    'recipients' => $recipient,
                    'recipients_emails' => [$recipient->getEmail()],
                    'template_name' => $templateName,
                    'template_params' => $templateParams,
                    'message' => $exception->getMessage(),
                ]
            );

        $this->sender->sendEmailTemplate($from, $recipient, $templateName, $templateParams);
    }

    public function testSendEmailTemplate(): void
    {
        $from = From::emailAddress('no-reply@example.com');
        $recipient = (new UserStub(42))
            ->setEmail('recipient@example.com');
        $templateName = 'sample_template';
        $templateParams = ['sample_key' => 'sample_value'];

        $emailModel = new EmailModel();
        $this->emailModelFromEmailTemplateFactory
            ->expects(self::once())
            ->method('createEmailModel')
            ->with($from, $recipient, $templateName, $templateParams)
            ->willReturn($emailModel);

        $emailUser = $this->createMock(EmailUser::class);
        $this->emailModelSender
            ->expects(self::once())
            ->method('send')
            ->with($emailModel, $emailModel->getOrigin())
            ->willReturn($emailUser);

        $this->assertLoggerNotCalled();

        self::assertSame(
            $emailUser,
            $this->sender->sendEmailTemplate($from, $recipient, $templateName, $templateParams)
        );
    }
}
