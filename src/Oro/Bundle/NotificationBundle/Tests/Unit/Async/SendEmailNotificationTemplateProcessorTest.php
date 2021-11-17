<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Manager\EmailTemplateManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\NotificationBundle\Async\SendEmailNotificationTemplateProcessor;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class SendEmailNotificationTemplateProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private const RECIPIENT_USER_ID = 7;

    private EmailTemplateManager|\PHPUnit\Framework\MockObject\MockObject $emailTemplateManager;

    private SendEmailNotificationTemplateProcessor $processor;

    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->emailTemplateManager = $this->createMock(EmailTemplateManager::class);

        $this->processor = new SendEmailNotificationTemplateProcessor(
            $managerRegistry,
            $this->emailTemplateManager
        );
        $this->setUpLoggerMock($this->processor);

        $this->entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($this->entityManager);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [Topics::SEND_NOTIFICATION_EMAIL_TEMPLATE],
            SendEmailNotificationTemplateProcessor::getSubscribedTopics()
        );
    }

    /**
     * @dataProvider processRejectsMessageWhenBodyIsInvalidDataProvider
     *
     * @param array $messageBody
     */
    public function testProcessRejectsMessageWhenBodyIsInvalid(array $messageBody): void
    {
        $this->loggerMock
            ->expects(self::once())
            ->method('critical')
            ->with('Message properties from, recipientUserId, template were not expected to be empty');

        $this->emailTemplateManager
            ->expects(self::never())
            ->method(self::anything());

        $message = new Message();
        $message->setBody(json_encode($messageBody));

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function processRejectsMessageWhenBodyIsInvalidDataProvider(): array
    {
        return [
            'empty message body' => [[]],
            'from present' => [['from' => 'from@example.com']],
            'recipientUserId present' => [['recipientUserId' => 142]],
            'template present' => [['template' => 'sample_template']],
            'from missing' => [
                [
                    'recipientUserId' => 142,
                    'template' => 'sample_template',
                ],
            ],
            'recipientUserId missing' => [
                [
                    'from' => 'from@example.com',
                    'template' => 'sample_template',
                ],
            ],
            'template missing' => [
                [
                    'from' => 'from@example.com',
                    'recipientUserId' => 142,
                ],
            ],
        ];
    }

    public function testProcessRejectsMessageWhenTemplateParamsIsNotArray(): void
    {
        $this->loggerMock
            ->expects(self::once())
            ->method('critical')
            ->with('Message property "templateParams" was expected to be array');

        $this->emailTemplateManager
            ->expects(self::never())
            ->method(self::anything());

        $message = new Message();
        $messageBody = [
            'from' => 'from@example.com',
            'recipientUserId' => 142,
            'template' => 'sample_template',
            'templateParams' => 'invalid_params',
        ];
        $message->setBody(json_encode($messageBody));

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessRejectsMessageWhenRecipientIsNotFound(): void
    {
        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('User with id "142" was not found');

        $this->emailTemplateManager
            ->expects(self::never())
            ->method(self::anything());

        $message = new Message();
        $messageBody = [
            'from' => 'from@example.com',
            'recipientUserId' => 142,
            'template' => 'sample_template',
            'templateParams' => [],
        ];
        $message->setBody(json_encode($messageBody));

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    /**
     * @dataProvider processSendsEmailDataProvider
     *
     * @param int $sentCount
     * @param string $expectedStatus
     */
    public function testProcessSendsEmail(int $sentCount, string $expectedStatus): void
    {
        $messageBody = [
            'from' => 'from@example.com',
            'recipientUserId' => 42,
            'template' => 'sample_template',
            'templateParams' => ['sample_key' => 'sample_value'],
            'templateEntity' => \stdClass::class,
        ];

        $user = new User();
        $this->entityManager
            ->expects(self::once())
            ->method('getReference')
            ->with(User::class, 42)
            ->willReturn($user);

        $this->emailTemplateManager
            ->expects(self::once())
            ->method('sendTemplateEmail')
            ->with(
                From::emailAddress($messageBody['from']),
                [$user],
                new EmailTemplateCriteria($messageBody['template'], $messageBody['templateEntity']),
                $messageBody['templateParams']
            )
            ->willReturn($sentCount);

        $message = new Message();
        $message->setBody(json_encode($messageBody));

        self::assertEquals(
            $expectedStatus,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function processSendsEmailDataProvider(): array
    {
        return [
            'ack when sentCount not 0' => [
                'sentCount' => 1,
                'expectedStatus' => MessageProcessorInterface::ACK,
            ],
            'reject when sentCount 0' => [
                'sentCount' => 0,
                'expectedStatus' => MessageProcessorInterface::REJECT,
            ],
        ];
    }
}
