<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\NotificationBundle\Model\TemplateMassNotification;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;

class MassNotificationSenderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const TEST_SENDER_EMAIL = 'admin@example.com';
    const TEST_SENDER_NAME  = 'sender name';
    const TEMPLATE_NAME     = 'test template';

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManagerInterface */
    private $entityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UserRepository */
    private $userRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityPool */
    private $entityPool;

    /** @var \PHPUnit\Framework\MockObject\MockObject|NotificationSettings */
    private $notificationSettings;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EmailNotificationManager */
    private $manager;

    /** @var MassNotificationSender */
    private $sender;

    /** @var array */
    private $massNotificationParams;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->entityPool = $this->createMock(EntityPool::class);
        $this->notificationSettings = $this->createMock(NotificationSettings::class);
        $this->manager = $this->createMock(EmailNotificationManager::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManager')
            ->willReturn($this->entityManager);

        $this->sender = new MassNotificationSender(
            $this->manager,
            $this->notificationSettings,
            $doctrine,
            $this->entityPool
        );
    }

    public function testSendToActiveUsersWithEmptySender()
    {
        $body = 'Test Body';
        $subject = 'Test Subject';

        $this->configureNotificationSettins([]);

        $recipient1 = ['id' => 333, 'email' => 'test1@test.com'];
        $recipient2 = ['id' => 777, 'email' => 'test2@test.com'];
        $recipient1Proxy = $this->createMock(Proxy::class);
        $recipient2Proxy = $this->createMock(Proxy::class);
        $this->entityManager->expects($this->exactly(2))
            ->method('getReference')
            ->withConsecutive(
                [User::class, $recipient1['id']],
                [User::class, $recipient2['id']]
            )
            ->willReturnOnConsecutiveCalls(
                $recipient1Proxy,
                $recipient2Proxy
            );
        $this->userRepository->expects($this->once())
            ->method('findEnabledUserEmails')
            ->willReturn([$recipient1, $recipient2]);

        $expectedMassNotification = new TemplateMassNotification(
            From::emailAddress(self::TEST_SENDER_EMAIL, self::TEST_SENDER_NAME),
            [
                new EmailAddressWithContext($recipient1['email'], $recipient1Proxy),
                new EmailAddressWithContext($recipient2['email'], $recipient2Proxy),
            ],
            new EmailTemplateCriteria(self::TEMPLATE_NAME),
            $subject
        );

        $this->manager->expects($this->once())
            ->method('process')
            ->with(
                [$expectedMassNotification],
                null,
                [MassNotificationSender::MAINTENANCE_VARIABLE => $body]
            );

        $this->entityPool->expects($this->once())
            ->method('persistAndFlush')
            ->with($this->entityManager);

        self::assertEquals(2, $this->sender->send($body, $subject));
    }

    public function testSendToConfigEmailsWithEmptyTemplate()
    {
        $body = "Test Body";
        $subject = null;
        $senderName = "Sender Name";
        $senderEmail = "sender@test.com";
        $recipientEmails = ['test1@test.com', 'test2@test.com'];

        $this->configureNotificationSettins($recipientEmails);

        $this->massNotificationParams = [
            'sender_name'      => $senderName,
            'sender_email'     => $senderEmail,
            'recipients'       => [
                new EmailAddressWithContext(reset($recipientEmails)),
                new EmailAddressWithContext(end($recipientEmails)),
            ]
        ];

        $expectedMassNotification = new TemplateMassNotification(
            From::emailAddress($senderEmail, $senderName),
            [
                new EmailAddressWithContext(reset($recipientEmails)),
                new EmailAddressWithContext(end($recipientEmails)),
            ],
            new EmailTemplateCriteria(self::TEMPLATE_NAME)
        );

        $this->manager->expects($this->once())
            ->method('process')
            ->with(
                [$expectedMassNotification],
                null,
                [MassNotificationSender::MAINTENANCE_VARIABLE => $body]
            );

        self::assertEquals(2, $this->sender->send($body, $subject, From::emailAddress($senderEmail, $senderName)));
    }

    /**
     * @param array $recipientEmails
     */
    private function configureNotificationSettins(array $recipientEmails): void
    {
        $this->notificationSettings
            ->expects($this->any())
            ->method('getSender')
            ->willReturn(From::emailAddress(self::TEST_SENDER_EMAIL, self::TEST_SENDER_NAME));

        $this->notificationSettings
            ->expects($this->any())
            ->method('getMassNotificationEmailTemplateName')
            ->willReturn(self::TEMPLATE_NAME);

        $this->notificationSettings
            ->expects($this->any())
            ->method('getMassNotificationRecipientEmails')
            ->willReturn($recipientEmails);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);
    }
}
