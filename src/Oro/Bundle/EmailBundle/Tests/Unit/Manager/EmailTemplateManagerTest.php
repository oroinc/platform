<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Manager\EmailTemplateManager;
use Oro\Bundle\EmailBundle\Model\DTO\LocalizedTemplateDTO;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\UserBundle\Entity\User;

class EmailTemplateManagerTest extends \PHPUnit\Framework\TestCase
{
    private const EMAIL_SUBJECT = 'Subject';
    private const EMAIL_BODY = 'Body';
    private const EMAIL_SUBJECT_GERMAN = 'Subject German';
    private const EMAIL_BODY_GERMAN = 'Body German';
    private const EMAIL_TEMPLATE_NAME = 'template_name';
    private const EMAIL_TEMPLATE_ENTITY_NAME = 'Template\Entity';
    private const TEMPLATE_PARAMS = ['param' => 'value'];

    /** @var \Swift_Mailer|\PHPUnit\Framework\MockObject\MockObject */
    private $mailer;

    /** @var Processor|\PHPUnit\Framework\MockObject\MockObject */
    private $processor;

    /** @var LocalizedTemplateProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $localizedTemplateProvider;

    /** @var EmailTemplateManager */
    private $manager;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(\Swift_Mailer::class);
        $this->processor = $this->createMock(Processor::class);
        $this->localizedTemplateProvider = $this->createMock(LocalizedTemplateProvider::class);

        $this->manager = new EmailTemplateManager(
            $this->mailer,
            $this->processor,
            $this->localizedTemplateProvider
        );
    }

    public function testSendTemplateEmailToOneRecipientWithTextMimeTypeAndSendFailed(): void
    {
        $this->processor
            ->expects($this->once())
            ->method('processEmbeddedImages')
            ->with($this->assertMessageCallback(
                self::EMAIL_SUBJECT,
                self::EMAIL_BODY,
                EmailTemplate::CONTENT_TYPE_TEXT,
                ['no-reply@mail.com' => null]
            ));

        $emailTemplateModel = (new EmailTemplate())
            ->setSubject(self::EMAIL_SUBJECT)
            ->setContent(self::EMAIL_BODY)
            ->setType(EmailTemplate::CONTENT_TYPE_TEXT);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->assertMessageCallback(
                self::EMAIL_SUBJECT,
                self::EMAIL_BODY,
                EmailTemplate::CONTENT_TYPE_TEXT,
                ['no-reply@mail.com' => null],
                ['to@mail.com' => null]
            ))
            ->willReturnCallback(function ($message, &$failedRecipients) {
                $failedRecipients = ['to@mail.com'];
            });

        $recipient = new EmailAddressWithContext('to@mail.com');

        $emailTemplateCriteria = new EmailTemplateCriteria(self::EMAIL_TEMPLATE_NAME, self::EMAIL_TEMPLATE_ENTITY_NAME);

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with(
                [$recipient],
                $emailTemplateCriteria,
                []
            )
            ->willReturn([
                (new LocalizedTemplateDTO($emailTemplateModel))->addRecipient($recipient),
            ]);

        $failedRecipients = [];

        self::assertEquals(
            0,
            $this->manager->sendTemplateEmail(
                From::emailAddress('no-reply@mail.com'),
                [$recipient],
                new EmailTemplateCriteria(self::EMAIL_TEMPLATE_NAME, self::EMAIL_TEMPLATE_ENTITY_NAME),
                [],
                $failedRecipients
            )
        );

        self::assertEquals(['to@mail.com'], $failedRecipients);
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

        $this->processor
            ->expects($this->exactly(2))
            ->method('processEmbeddedImages')
            ->withConsecutive(
                [$this->assertMessageCallback(
                    self::EMAIL_SUBJECT,
                    self::EMAIL_BODY,
                    EmailTemplate::CONTENT_TYPE_HTML,
                    ['no-reply@mail.com' => 'SenderName']
                )],
                [$this->assertMessageCallback(
                    self::EMAIL_SUBJECT_GERMAN,
                    self::EMAIL_BODY_GERMAN,
                    EmailTemplate::CONTENT_TYPE_TEXT,
                    ['no-reply@mail.com' => 'SenderName']
                )]
            );

        $this->mailer
            ->expects($this->exactly(3))
            ->method('send')
            ->withConsecutive(
                [$this->assertMessageCallback(
                    self::EMAIL_SUBJECT,
                    self::EMAIL_BODY,
                    EmailTemplate::CONTENT_TYPE_HTML,
                    ['no-reply@mail.com' => 'SenderName'],
                    ['user1@mail.com' => null]
                )],
                [$this->assertMessageCallback(
                    self::EMAIL_SUBJECT,
                    self::EMAIL_BODY,
                    EmailTemplate::CONTENT_TYPE_HTML,
                    ['no-reply@mail.com' => 'SenderName'],
                    ['other_user@mail.com' => null]
                )],
                [$this->assertMessageCallback(
                    self::EMAIL_SUBJECT_GERMAN,
                    self::EMAIL_BODY_GERMAN,
                    EmailTemplate::CONTENT_TYPE_TEXT,
                    ['no-reply@mail.com' => 'SenderName'],
                    ['user2@mail.com' => null]
                )]
            )
            ->willReturn(1);

        $userRecipient1 = new EmailAddressWithContext('user1@mail.com');
        $userRecipient2 = new EmailAddressWithContext('user2@mail.com');
        $userRecipient3 = new EmailAddressWithContext('other_user@mail.com', new User());

        $emailTemplateCriteria = new EmailTemplateCriteria(self::EMAIL_TEMPLATE_NAME, self::EMAIL_TEMPLATE_ENTITY_NAME);

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with(
                [$userRecipient1, $userRecipient2, $userRecipient3],
                $emailTemplateCriteria,
                self::TEMPLATE_PARAMS
            )
            ->willReturn([
                (new LocalizedTemplateDTO($emailTemplate))
                    ->addRecipient($userRecipient1)
                    ->addRecipient($userRecipient3),

                (new LocalizedTemplateDTO($germanEmailTemplate))
                    ->addRecipient($userRecipient2),
            ]);

        self::assertEquals(
            3,
            $this->manager->sendTemplateEmail(
                From::emailAddress('no-reply@mail.com', 'SenderName'),
                [$userRecipient1, $userRecipient2, $userRecipient3],
                $emailTemplateCriteria,
                self::TEMPLATE_PARAMS
            )
        );
    }

    /**
     * We have to use callback instead of constructing \Swift_Message object, as it has auto generated inner fields
     * which make it impossible to compare objects directly.
     */
    private function assertMessageCallback(
        string $subject,
        string $body,
        string $contentType,
        array $from,
        array $to = null
    ): \PHPUnit\Framework\Constraint\Callback {
        return $this->callback(function (\Swift_Message $message) use ($subject, $body, $contentType, $to, $from) {
            $this->assertEquals($subject, $message->getSubject());
            $this->assertEquals($body, $message->getBody());
            $this->assertEquals($contentType, $message->getContentType());
            $this->assertEquals($to, $message->getTo());
            $this->assertEquals($from, $message->getFrom());

            return true;
        });
    }
}
