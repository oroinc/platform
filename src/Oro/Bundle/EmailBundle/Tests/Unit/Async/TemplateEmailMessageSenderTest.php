<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\TemplateEmailMessageSender;
use Oro\Bundle\EmailBundle\Manager\TemplateEmailManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;

class TemplateEmailMessageSenderTest extends \PHPUnit\Framework\TestCase
{
    private const RECIPIENT_USER_ID = 7;
    use EntityTrait;

    /**
     * @var TemplateEmailManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $templateEmailManager;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var TemplateEmailMessageSender
     */
    private $sender;

    protected function setUp()
    {
        $this->templateEmailManager  = $this->createMock(TemplateEmailManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->sender = new TemplateEmailMessageSender($this->templateEmailManager, $this->doctrineHelper);
    }

    /**
     * @dataProvider badMessageProvider
     * @param array $message
     */
    public function testIsTranslatableWhenMessageHasNoRequiredFields(array $message): void
    {
        self::assertFalse($this->sender->isTranslatable($message));
    }

    /**
     * @return array
     */
    public function badMessageProvider(): array
    {
        return [
            'no template' => [
                'message' => [
                    'sender' => From::emailAddress('from@mail.com', 'From Name')->toArray(),
                    'body' => [],
                    'recipientUserId' => self::RECIPIENT_USER_ID
                ]
            ],
            'no sender' => [
                'message' => [
                    'template' => 'template_name',
                    'body' => [],
                    'recipientUserId' => self::RECIPIENT_USER_ID
                ]
            ],
            'no body' => [
                'message' => [
                    'template' => 'template_name',
                    'sender' => From::emailAddress('from@mail.com')->toArray(),
                    'recipientUserId' => self::RECIPIENT_USER_ID
                ]
            ],

            'no recipient user id' => [
                'message' => [
                    'template' => 'template_name',
                    'sender' => From::emailAddress('from@mail.com')->toArray(),
                    'body' => []
                ]
            ]
        ];
    }

    public function testIsTranslatableWhenMessageHasRecipientUserId(): void
    {
        $message = [
            'template' => 'template_name',
            'sender' => From::emailAddress('from@mail.com', 'From Name')->toArray(),
            'body' => [],
            'recipientUserId' => self::RECIPIENT_USER_ID
        ];

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->with(User::class, self::RECIPIENT_USER_ID)
            ->willReturn($this->getEntity(User::class, ['id' => self::RECIPIENT_USER_ID]));

        self::assertTrue($this->sender->isTranslatable($message));
    }

    /**
     * @dataProvider badMessageProvider
     * @param array $message
     */
    public function testSendTranslatedMessageWhenMessageIsNotTranslatable(array $message): void
    {
        $this->expectException(\LogicException::class);

        $this->sender->sendTranslatedMessage($message);
    }

    public function testSendTranslatedMessageWithFailedRecipients(): void
    {
        $message = [
            'template' => 'template_name',
            'sender' => From::emailAddress('from@mail.com', 'From Name')->toArray(),
            'body' => [],
            'recipientUserId' => self::RECIPIENT_USER_ID
        ];

        $user = $this->getEntity(User::class, ['id' => self::RECIPIENT_USER_ID]);

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityReference')
            ->with(User::class, self::RECIPIENT_USER_ID)
            ->willReturn($user);

        $this->templateEmailManager
            ->expects($this->once())
            ->method('sendTemplateEmail')
            ->with(
                From::emailAddress('from@mail.com', 'From Name'),
                [$user],
                new EmailTemplateCriteria('template_name'),
                [],
                []
            )
            ->willReturnCallback(function ($from, $recipients, $emailTemplateCriteria, $params, &$failedRecipients) {
                $failedRecipients = ['user@mail.com'];

                return 0;
            });

        $failedRecipients = [];
        self::assertEquals(0, $this->sender->sendTranslatedMessage($message, $failedRecipients));
        self::assertEquals(['user@mail.com'], $failedRecipients);
    }

    public function testSendTranslatedMessage(): void
    {
        $message = [
            'template' => 'template_name',
            'sender' => From::emailAddress('from@mail.com', 'From Name')->toArray(),
            'body' => [],
            'recipientUserId' => self::RECIPIENT_USER_ID
        ];

        $user = $this->getEntity(User::class, ['id' => self::RECIPIENT_USER_ID]);

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityReference')
            ->with(User::class, self::RECIPIENT_USER_ID)
            ->willReturn($user);

        $this->templateEmailManager
            ->expects($this->once())
            ->method('sendTemplateEmail')
            ->with(
                From::emailAddress('from@mail.com', 'From Name'),
                [$user],
                new EmailTemplateCriteria('template_name'),
                [],
                []
            )
            ->willReturn(1);

        $failedRecipients = [];
        self::assertEquals(1, $this->sender->sendTranslatedMessage($message, $failedRecipients));
        self::assertEmpty($failedRecipients);
    }
}
