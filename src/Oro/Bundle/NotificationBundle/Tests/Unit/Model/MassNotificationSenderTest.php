<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\MassNotification;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;

class MassNotificationSenderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_SENDER_EMAIL = 'admin@example.com';
    const TEST_SENDER_NAME  = 'sender name';
    const TEMPLATE_NAME     = 'test template';

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $entityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|UserRepository */
    protected $userRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EmailTemplateRepository */
    protected $templateRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityPool */
    protected $entityPool;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager */
    protected $cm;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EmailNotificationManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DQLNameFormatter */
    protected $dqlNameFormatter;

    /** @var MassNotificationSender */
    protected $sender;

    /** @var  array */
    protected $massNotificationParams;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->templateRepository = $this->createMock(EmailTemplateRepository::class);
        $this->entityPool = $this->createMock(EntityPool::class);
        $this->cm = $this->createMock(ConfigManager::class);
        $this->dqlNameFormatter = $this->createMock(DQLNameFormatter::class);
        $this->manager = $this->createMock(EmailNotificationManager::class);
        $this->sender = new MassNotificationSender(
            $this->manager,
            $this->cm,
            $this->entityManager,
            $this->entityPool,
            $this->dqlNameFormatter
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->entityManager,
            $this->cm,
            $this->entityPool,
            $this->manager,
            $this->sender,
            $this->templateRepository,
            $this->userRepository,
            $this->dqlNameFormatter
        );
    }

    public function testSendToActiveUsersWithEmptySender()
    {
        $body = "Test Body";
        $subject = "Test Subject";
        $userRecipients = [
            ['name' => 'test1', 'email' => 'test1@test.com'],
            ['name' => 'test2', 'email' => 'test2@test.com']
        ];

        $this->cm->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['oro_notification.email_notification_sender_email', false, false, null, self::TEST_SENDER_EMAIL],
                ['oro_notification.email_notification_sender_name', false, false, null, self::TEST_SENDER_NAME],
                ['oro_notification.mass_notification_recipients', false, false, null, ''],
                ['oro_notification.mass_notification_template', false, false, null, self::TEMPLATE_NAME]
            ]));

        $this->entityManager->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive(['OroUserBundle:User'], ['OroEmailBundle:EmailTemplate'])
            ->willReturnOnConsecutiveCalls($this->userRepository, $this->templateRepository);

        $template = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailTemplate');
        $template->expects($this->once())->method('getType')->willReturn('html');
        $template->expects($this->once())->method('getContent')->willReturn('test content');
        $template->expects($this->once())->method('getSubject')->willReturn('subject');

        $this->templateRepository->expects($this->once())
            ->method('findByName')
            ->with(self::TEMPLATE_NAME)
            ->willReturn($template);

        $this->dqlNameFormatter->expects($this->once())
            ->method('getFormattedNameDQL')
            ->willReturn('ConcatExpression');

        $query = $this->createMock('Doctrine\ORM\AbstractQuery');
        $query->expects($this->any())
            ->method('getResult')
            ->willReturn($userRecipients);

        $queryBuilder = $this->createMock('Doctrine\ORM\QueryBuilder');
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('u.enabled = :enabled')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('enabled', true);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $this->userRepository->expects($this->once())
            ->method('getPrimaryEmailsQb')
            ->with('ConcatExpression')
            ->willReturn($queryBuilder);

        $this->massNotificationParams = [
            'sender_name'      => self::TEST_SENDER_NAME,
            'sender_email'     => self::TEST_SENDER_EMAIL,
            'recipients'       => ['test1@test.com', 'test2@test.com'],
            'template_type'    => 'html',
            'template_content' => 'test content',
            'template_subject' => $subject
        ];
        $this->manager->expects($this->once())
            ->method('process')
            ->with(
                null,
                $this->callback([$this, 'assertMassNotification']),
                null,
                [MassNotificationSender::MAINTENANCE_VARIABLE => $body]
            );

        $this->entityPool->expects($this->once())
            ->method('persistAndFlush')
            ->with($this->entityManager);

        self::assertEquals(2, $this->sender->send($body, $subject));
    }

    public function testSendToConfigEmailsWithEmtpyTemplate()
    {
        $body = "Test Body";
        $subject = null;
        $senderName = "Sender Name";
        $senderEmail = "sender@test.com";
        $configRecipients = 'test1@test.com;test2@test.com';

        $this->cm->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['oro_notification.mass_notification_recipients', false, false, null, $configRecipients],
                ['oro_notification.mass_notification_template', false, false, null, self::TEMPLATE_NAME]
            ]));
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:EmailTemplate')
            ->will($this->returnValue($this->templateRepository));
        $this->templateRepository->expects($this->once())
            ->method('findByName')
            ->with(self::TEMPLATE_NAME)
            ->willReturn(null);

        $this->massNotificationParams = [
            'sender_name'      => $senderName,
            'sender_email'     => $senderEmail,
            'recipients'       => explode(';', $configRecipients),
            'template_type'    => 'txt',
            'template_content' => sprintf("{{ %s }}", MassNotificationSender::MAINTENANCE_VARIABLE),
            'template_subject' => $subject
        ];

        $this->manager->expects($this->once())
            ->method('process')
            ->with(
                null,
                $this->callback([$this, 'assertMassNotification']),
                null,
                [MassNotificationSender::MAINTENANCE_VARIABLE => $body]
            );

        self::assertEquals(2, $this->sender->send($body, $subject, $senderEmail, $senderName));
    }

    /**
     * @param array $massNotifications
     * @return bool
     */
    public function assertMassNotification($massNotifications)
    {
        $params = $this->massNotificationParams;

        /** @var MassNotification $massNotification */
        $massNotification = current($massNotifications);
        self::assertEquals($params['sender_name'], $massNotification->getSenderName());
        self::assertEquals($params['sender_email'], $massNotification->getSenderEmail());
        self::assertEquals($params['recipients'], $massNotification->getRecipientEmails());

        $template = $massNotification->getTemplate();
        self::assertTrue($template instanceof EmailTemplateInterface);
        self::assertEquals($params['template_type'], $template->getType());
        self::assertEquals($params['template_content'], $template->getContent());
        self::assertEquals($params['template_subject'], $template->getSubject());

        return true;
    }
}
