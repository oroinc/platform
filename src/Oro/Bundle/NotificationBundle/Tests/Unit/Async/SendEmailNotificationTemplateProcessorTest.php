<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\NotificationBundle\Async\SendEmailNotificationTemplateProcessor;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTemplateTopic;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SendEmailNotificationTemplateProcessorTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private const RECIPIENT_USER_ID = 7;

    private EmailNotificationManager|MockObject $emailNotificationManager;
    private EntityManagerInterface|MockObject $entityManager;
    private SendEmailNotificationTemplateProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->emailNotificationManager = $this->createMock(EmailNotificationManager::class);

        $this->processor = new SendEmailNotificationTemplateProcessor(
            $managerRegistry,
            $this->emailNotificationManager
        );
        $this->setUpLoggerMock($this->processor);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $managerRegistry->expects(self::any())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($this->entityManager);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [SendEmailNotificationTemplateTopic::getName()],
            SendEmailNotificationTemplateProcessor::getSubscribedTopics()
        );
    }

    public function testProcessRejectsMessageWhenRecipientIsNotFound(): void
    {
        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with('User with id "142" was not found');

        $this->emailNotificationManager->expects(self::never())
            ->method(self::anything());

        $message = new Message();
        $messageBody = [
            'from' => 'from@example.com',
            'recipientUserId' => 142,
            'template' => 'sample_template',
            'templateParams' => [],
        ];
        $message->setBody($messageBody);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessSendsEmail(): void
    {
        $messageBody = [
            'from' => 'from@example.com',
            'recipientUserId' => 42,
            'template' => 'sample_template',
            'templateParams' => ['sample_key' => 'sample_value'],
            'templateEntity' => \stdClass::class,
        ];

        $user = new User();
        $this->entityManager->expects(self::once())
            ->method('getReference')
            ->with(User::class, 42)
            ->willReturn($user);

        $this->emailNotificationManager->expects(self::once())
            ->method('processSingle')
            ->with(
                new TemplateEmailNotification(
                    new EmailTemplateCriteria($messageBody['template'], $messageBody['templateEntity']),
                    [$user],
                    null,
                    From::emailAddress($messageBody['from'])
                ),
                $messageBody['templateParams']
            );

        $message = new Message();
        $message->setBody($messageBody);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }
}
