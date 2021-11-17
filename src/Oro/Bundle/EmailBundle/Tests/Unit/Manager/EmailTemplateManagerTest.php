<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesInSymfonyEmailHandler;
use Oro\Bundle\EmailBundle\Manager\EmailTemplateManager;
use Oro\Bundle\EmailBundle\Model\DTO\LocalizedTemplateDTO;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email as SymfonyEmail;

class EmailTemplateManagerTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private const EMAIL_SUBJECT = 'Subject';
    private const EMAIL_BODY = 'Body';
    private const EMAIL_SUBJECT_GERMAN = 'Subject German';
    private const EMAIL_BODY_GERMAN = 'Body German';
    private const EMAIL_TEMPLATE_NAME = 'template_name';
    private const EMAIL_TEMPLATE_ENTITY_NAME = 'Template\Entity';
    private const TEMPLATE_PARAMS = ['param' => 'value'];

    private MailerInterface|\PHPUnit\Framework\MockObject\MockObject $mailer;

    private EmbeddedImagesInSymfonyEmailHandler|\PHPUnit\Framework\MockObject\MockObject $embeddedImagesHandler;

    private LocalizedTemplateProvider|\PHPUnit\Framework\MockObject\MockObject $localizedTemplateProvider;

    private EmailTemplateManager $manager;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->embeddedImagesHandler = $this->createMock(EmbeddedImagesInSymfonyEmailHandler::class);
        $this->localizedTemplateProvider = $this->createMock(LocalizedTemplateProvider::class);

        $this->manager = new EmailTemplateManager(
            $this->mailer,
            $this->embeddedImagesHandler,
            $this->localizedTemplateProvider
        );

        $this->setUpLoggerMock($this->manager);
    }

    public function testSendTemplateEmailToOneRecipientWithTextMimeTypeAndSendFailed(): void
    {
        $emailTemplateModel = (new EmailTemplate())
            ->setSubject(self::EMAIL_SUBJECT)
            ->setContent(self::EMAIL_BODY)
            ->setType(EmailTemplate::CONTENT_TYPE_TEXT);

        $exception = new \RuntimeException('Sample exception');
        $this->mailer->expects(self::once())
            ->method('send')
            ->with(
                $this->assertMessageCallback(
                    self::EMAIL_SUBJECT,
                    '',
                    self::EMAIL_BODY,
                    ['no-reply@example.com'],
                    ['to@example.com']
                )
            )
            ->willThrowException($exception);

        $recipient = new EmailAddressWithContext('to@example.com');
        $emailTemplateCriteria = new EmailTemplateCriteria(self::EMAIL_TEMPLATE_NAME, self::EMAIL_TEMPLATE_ENTITY_NAME);

        $this->localizedTemplateProvider->expects(self::once())
            ->method('getAggregated')
            ->with([$recipient], $emailTemplateCriteria, [])
            ->willReturn([(new LocalizedTemplateDTO($emailTemplateModel))->addRecipient($recipient)]);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Failed to send an email to "to@example.com" using "template_name" email template: Sample exception',
                ['exception' => $exception, 'criteria' => $emailTemplateCriteria]
            );

        self::assertEquals(
            0,
            $this->manager->sendTemplateEmail(
                From::emailAddress('no-reply@example.com'),
                [$recipient],
                new EmailTemplateCriteria(self::EMAIL_TEMPLATE_NAME, self::EMAIL_TEMPLATE_ENTITY_NAME),
                []
            )
        );
    }

    public function testSendTemplateEmailWithSeveralRecipients(): void
    {
        $emailTemplate = (new EmailTemplate())
            ->setSubject(self::EMAIL_SUBJECT)
            ->setContent(self::EMAIL_BODY)
            ->setType(EmailTemplate::CONTENT_TYPE_HTML);

        $germanEmailTemplate = (new EmailTemplate())
            ->setSubject(self::EMAIL_SUBJECT_GERMAN)
            ->setContent(self::EMAIL_BODY_GERMAN)
            ->setType(EmailTemplate::CONTENT_TYPE_TEXT);

        $this->embeddedImagesHandler->expects(self::once())
            ->method('handleEmbeddedImages')
            ->with(
                $this->assertMessageCallback(
                    self::EMAIL_SUBJECT,
                    self::EMAIL_BODY,
                    '',
                    ['SenderName <no-reply@example.com>'],
                    []
                )
            );

        $this->mailer->expects(self::exactly(3))
            ->method('send')
            ->withConsecutive(
                [
                    $this->assertMessageCallback(
                        self::EMAIL_SUBJECT,
                        self::EMAIL_BODY,
                        '',
                        ['SenderName <no-reply@example.com>'],
                        ['user1@example.com']
                    ),
                ],
                [
                    $this->assertMessageCallback(
                        self::EMAIL_SUBJECT,
                        self::EMAIL_BODY,
                        '',
                        ['SenderName <no-reply@example.com>'],
                        ['other_user@example.com']
                    ),
                ],
                [
                    $this->assertMessageCallback(
                        self::EMAIL_SUBJECT_GERMAN,
                        '',
                        self::EMAIL_BODY_GERMAN,
                        ['SenderName <no-reply@example.com>'],
                        ['user2@example.com']
                    ),
                ]
            );

        $userRecipient1 = new EmailAddressWithContext('user1@example.com');
        $userRecipient2 = new EmailAddressWithContext('user2@example.com');
        $userRecipient3 = new EmailAddressWithContext('other_user@example.com', new User());

        $emailTemplateCriteria = new EmailTemplateCriteria(self::EMAIL_TEMPLATE_NAME, self::EMAIL_TEMPLATE_ENTITY_NAME);

        $this->localizedTemplateProvider->expects(self::once())
            ->method('getAggregated')
            ->with(
                [$userRecipient1, $userRecipient2, $userRecipient3],
                $emailTemplateCriteria,
                self::TEMPLATE_PARAMS
            )
            ->willReturn(
                [
                    (new LocalizedTemplateDTO($emailTemplate))
                        ->addRecipient($userRecipient1)
                        ->addRecipient($userRecipient3),

                    (new LocalizedTemplateDTO($germanEmailTemplate))
                        ->addRecipient($userRecipient2),
                ]
            );

        self::assertEquals(
            3,
            $this->manager->sendTemplateEmail(
                From::emailAddress('no-reply@example.com', 'SenderName'),
                [$userRecipient1, $userRecipient2, $userRecipient3],
                $emailTemplateCriteria,
                self::TEMPLATE_PARAMS
            )
        );
    }

    /**
     * We have to use callback instead of constructing object, as it has auto generated inner fields
     * which make it impossible to compare objects directly.
     */
    private function assertMessageCallback(
        string $subject,
        string $htmlBody,
        string $textBody,
        array $from,
        array $to
    ): \PHPUnit\Framework\Constraint\Callback {
        return self::callback(function (SymfonyEmail $message) use ($subject, $htmlBody, $textBody, $to, $from) {
            $this->assertEquals($subject, $message->getSubject());
            $this->assertEquals($htmlBody, $message->getHtmlBody());
            $this->assertEquals($textBody, $message->getTextBody());
            $this->assertEquals(SymfonyAddress::createArray($from), $message->getFrom());
            $this->assertEquals(SymfonyAddress::createArray($to), $message->getTo());

            return true;
        });
    }
}
