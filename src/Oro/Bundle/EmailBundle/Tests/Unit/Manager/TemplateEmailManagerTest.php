<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Manager\TemplateEmailManager;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\LocaleBundle\Provider\PreferredLanguageProviderInterface;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\UserBundle\Entity\User;

class TemplateEmailManagerTest extends \PHPUnit_Framework_TestCase
{
    private const LANGUAGE = 'en_US';
    private const EMAIL_SUBJECT = 'Subject';
    private const EMAIL_BODY = 'Body';
    private const LANGUAGE_GERMAN = 'de_DE';
    private const EMAIL_SUBJECT_GERMAN = 'Subject German';
    private const EMAIL_BODY_GERMAN = 'Body German';
    private const EMAIL_TEMPLATE_NAME = 'template_name';
    private const EMAIL_TEMPLATE_ENTITY_NAME = 'Template\Entity';
    private const TEMPLATE_PARAMS = ['param' => 'value'];

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var \Swift_Mailer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mailer;

    /**
     * @var EmailRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $emailRenderer;

    /**
     * @var PreferredLanguageProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $languageProvider;

    /**
     * @var Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processor;

    /**
     * @var TemplateEmailManager
     */
    private $manager;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->mailer = $this->createMock(\Swift_Mailer::class);
        $this->emailRenderer = $this->createMock(EmailRenderer::class);
        $this->languageProvider = $this->createMock(PreferredLanguageProviderInterface::class);
        $this->processor = $this->createMock(Processor::class);

        $this->manager = new TemplateEmailManager(
            $this->registry,
            $this->mailer,
            $this->emailRenderer,
            $this->languageProvider,
            $this->processor
        );
    }

    public function testSendTemplateEmailWithInvalidRecipientObject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'recipients should be array of EmailHolderInterface values, "stdClass" type in array given.'
        );

        $this->manager->sendTemplateEmail(
            From::emailAddress('no-reply@mail.com'),
            [new \stdClass()],
            new EmailTemplateCriteria(self::EMAIL_TEMPLATE_NAME)
        );
    }

    public function testSendTemplateEmailWithInvalidRecipientType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'recipients should be array of EmailHolderInterface values, "boolean" type in array given.'
        );

        $this->manager->sendTemplateEmail(
            From::emailAddress('no-reply@mail.com'),
            [true],
            new EmailTemplateCriteria(self::EMAIL_TEMPLATE_NAME)
        );
    }

    public function testSendTemplateEmailWhenEmailTemplateNotFound(): void
    {
        $this->expectException(\LogicException::class);

        $recipient = new EmailAddressWithContext('to@mail.com');
        $this->languageProvider
            ->expects($this->any())
            ->method('getPreferredLanguage')
            ->willReturnMap([
                [$recipient, self::LANGUAGE]
            ]);

        $repository = $this->configureEmailTemplateRepository();
        $repository
            ->expects($this->once())
            ->method('findOneLocalized')
            ->with(new EmailTemplateCriteria(self::EMAIL_TEMPLATE_NAME), self::LANGUAGE)
            ->willReturn(null);

        $this->manager->sendTemplateEmail(
            From::emailAddress('no-reply@mail.com'),
            [$recipient],
            new EmailTemplateCriteria(self::EMAIL_TEMPLATE_NAME)
        );
    }

    public function testSendTemplateEmailToOneRecipientWithTextMimeTypeAndSendFailed(): void
    {
        $emailTemplate = (new EmailTemplate())->setType(EmailTemplate::TYPE_TEXT);
        $repository = $this->configureEmailTemplateRepository();
        $repository
            ->expects($this->once())
            ->method('findOneLocalized')
            ->with(
                new EmailTemplateCriteria(self::EMAIL_TEMPLATE_NAME, self::EMAIL_TEMPLATE_ENTITY_NAME),
                self::LANGUAGE
            )
            ->willReturn($emailTemplate);

        $this->emailRenderer
            ->expects($this->once())
            ->method('compileMessage')
            ->with($emailTemplate, [])
            ->willReturn([self::EMAIL_SUBJECT, self::EMAIL_BODY]);

        $this->processor
            ->expects($this->once())
            ->method('processEmbeddedImages')
            ->with($this->assertMessageCallback(
                self::EMAIL_SUBJECT,
                self::EMAIL_BODY,
                TemplateEmailManager::CONTENT_TYPE_TEXT,
                ['no-reply@mail.com' => null]
            ));

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->assertMessageCallback(
                self::EMAIL_SUBJECT,
                self::EMAIL_BODY,
                TemplateEmailManager::CONTENT_TYPE_TEXT,
                ['no-reply@mail.com' => null],
                ['to@mail.com' => null]
            ))
            ->willReturnCallback(function ($message, &$failedRecipients) {
                $failedRecipients = ['to@mail.com'];
            });

        $recipient = new EmailAddressWithContext('to@mail.com');
        $this->languageProvider
            ->expects($this->any())
            ->method('getPreferredLanguage')
            ->willReturnMap([
                [$recipient, self::LANGUAGE]
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
        $emailTemplate = (new EmailTemplate())->setType(EmailTemplate::TYPE_HTML);
        $germanEmailTemplate = (new EmailTemplate())->setType(EmailTemplate::TYPE_TEXT);

        $repository = $this->configureEmailTemplateRepository();
        $repository
            ->expects($this->exactly(2))
            ->method('findOneLocalized')
            ->withConsecutive(
                [
                    new EmailTemplateCriteria(self::EMAIL_TEMPLATE_NAME, self::EMAIL_TEMPLATE_ENTITY_NAME),
                    self::LANGUAGE
                ],
                [
                    new EmailTemplateCriteria(self::EMAIL_TEMPLATE_NAME, self::EMAIL_TEMPLATE_ENTITY_NAME),
                    self::LANGUAGE_GERMAN
                ]
            )
            ->willReturnOnConsecutiveCalls($emailTemplate, $germanEmailTemplate);

        $this->emailRenderer
            ->expects($this->exactly(2))
            ->method('compileMessage')
            ->withConsecutive([$emailTemplate, self::TEMPLATE_PARAMS], [$germanEmailTemplate, self::TEMPLATE_PARAMS])
            ->willReturnOnConsecutiveCalls(
                [self::EMAIL_SUBJECT, self::EMAIL_BODY],
                [self::EMAIL_SUBJECT_GERMAN, self::EMAIL_BODY_GERMAN]
            );

        $this->processor
            ->expects($this->exactly(2))
            ->method('processEmbeddedImages')
            ->withConsecutive(
                $this->assertMessageCallback(
                    self::EMAIL_SUBJECT,
                    self::EMAIL_BODY,
                    TemplateEmailManager::CONTENT_TYPE_HTML,
                    ['no-reply@mail.com' => 'SenderName']
                ),
                $this->assertMessageCallback(
                    self::EMAIL_SUBJECT_GERMAN,
                    self::EMAIL_BODY_GERMAN,
                    TemplateEmailManager::CONTENT_TYPE_HTML,
                    ['no-reply@mail.com' => 'SenderName']
                )
            );

        $this->mailer
            ->expects($this->exactly(3))
            ->method('send')
            ->withConsecutive(
                [$this->assertMessageCallback(
                    self::EMAIL_SUBJECT,
                    self::EMAIL_BODY,
                    TemplateEmailManager::CONTENT_TYPE_HTML,
                    ['no-reply@mail.com' => 'SenderName'],
                    ['user1@mail.com' => null]
                )],
                [$this->assertMessageCallback(
                    self::EMAIL_SUBJECT,
                    self::EMAIL_BODY,
                    TemplateEmailManager::CONTENT_TYPE_HTML,
                    ['no-reply@mail.com' => 'SenderName'],
                    ['other_user@mail.com' => null]
                )],
                [$this->assertMessageCallback(
                    self::EMAIL_SUBJECT_GERMAN,
                    self::EMAIL_BODY_GERMAN,
                    TemplateEmailManager::CONTENT_TYPE_TEXT,
                    ['no-reply@mail.com' => 'SenderName'],
                    ['user2@mail.com' => null]
                )]
            )
            ->willReturn(1);

        $userRecipient1 = new EmailAddressWithContext('user1@mail.com');
        $userRecipient2 = new EmailAddressWithContext('user2@mail.com');
        $userRecipient3 = new EmailAddressWithContext('other_user@mail.com', new User());

        $this->languageProvider
            ->expects($this->any())
            ->method('getPreferredLanguage')
            ->willReturnMap([
                [$userRecipient1, self::LANGUAGE],
                [$userRecipient2, self::LANGUAGE_GERMAN],
                [$userRecipient3, self::LANGUAGE]
            ]);

        self::assertEquals(
            3,
            $this->manager->sendTemplateEmail(
                From::emailAddress('no-reply@mail.com', 'SenderName'),
                [$userRecipient1, $userRecipient2, $userRecipient3],
                new EmailTemplateCriteria(self::EMAIL_TEMPLATE_NAME, self::EMAIL_TEMPLATE_ENTITY_NAME),
                self::TEMPLATE_PARAMS
            )
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function configureEmailTemplateRepository(): \PHPUnit_Framework_MockObject_MockObject
    {
        $repository = $this->createMock(EmailTemplateRepository::class);

        $manager = $this->createMock(EntityManager::class);
        $manager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [EmailTemplate::class, $repository]
            ]);

        $this->registry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [EmailTemplate::class, $manager]
            ]);

        return $repository;
    }

    /**
     * We have to use callback instead of constructing \Swift_Message object, as it has auto generated inner fields
     * which make it impossible to compare objects directly.
     *
     * @param string $subject
     * @param string $body
     * @param string $contentType
     * @param array $from
     * @param array|null $to
     * @return \PHPUnit_Framework_Constraint_Callback
     */
    private function assertMessageCallback(
        string $subject,
        string $body,
        string $contentType,
        array $from,
        array $to = null
    ): \PHPUnit_Framework_Constraint_Callback {
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
