<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Model;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\NotificationBundle\DependencyInjection\Configuration;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Oro\Bundle\NotificationBundle\Model\TemplateMassNotification;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;

class MassNotificationSenderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

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
        $body = 'Test Body';
        $subject = 'Test Subject';

        $this->cm->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['oro_notification.email_notification_sender_email', false, false, null, self::TEST_SENDER_EMAIL],
                ['oro_notification.email_notification_sender_name', false, false, null, self::TEST_SENDER_NAME],
                ['oro_notification.mass_notification_recipients', false, false, null, ''],
                ['oro_notification.mass_notification_template', false, false, null, self::TEMPLATE_NAME]
            ]));

        $this->entityManager->expects($this->exactly(3))
            ->method('getRepository')
            ->withConsecutive(
                ['OroUserBundle:User'],
                ['OroEmailBundle:EmailTemplate'],
                ['OroEmailBundle:EmailTemplate']
            )
            ->willReturnOnConsecutiveCalls(
                $this->userRepository,
                $this->templateRepository,
                $this->templateRepository
            );

        $template = $this->getEntity(EmailTemplate::class, [
            'type' => 'html',
            'content' => 'test content',
            'subject' => 'subject',
        ]);

        $this->templateRepository->expects($this->once())
            ->method('findByName')
            ->with(self::TEMPLATE_NAME)
            ->willReturn($template);

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

        $this->massNotificationParams = [
            'sender_name'      => self::TEST_SENDER_NAME,
            'sender_email'     => self::TEST_SENDER_EMAIL,
            'recipients'       => [
                new EmailAddressWithContext($recipient1['email'], $recipient1Proxy),
                new EmailAddressWithContext($recipient2['email'], $recipient2Proxy),
            ],
            'recipient_emails' => [$recipient1['email'], $recipient2['email']],
            'template_type'    => 'html',
            'template_content' => 'test content',
            'template_subject' => $subject,
            'template_criteria' => new EmailTemplateCriteria(self::TEMPLATE_NAME)
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
        $this->templateRepository->expects($this->once())
            ->method('isExist')
            ->with(new EmailTemplateCriteria(self::TEMPLATE_NAME))
            ->willReturn(true);

        self::assertEquals(2, $this->sender->send($body, $subject));
    }

    public function testSendToConfigEmailsWithEmptyTemplate()
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
        $this->entityManager->expects($this->exactly(2))
            ->method('getRepository')
            ->with('OroEmailBundle:EmailTemplate')
            ->will($this->returnValue($this->templateRepository));
        $this->templateRepository->expects($this->once())
            ->method('findByName')
            ->with(self::TEMPLATE_NAME)
            ->willReturn(null);

        $recipientEmails = explode(';', $configRecipients);
        $this->massNotificationParams = [
            'sender_name'      => $senderName,
            'sender_email'     => $senderEmail,
            'recipients'       => [
                new EmailAddressWithContext(reset($recipientEmails)),
                new EmailAddressWithContext(end($recipientEmails)),
            ],
            'recipient_emails'  => $recipientEmails,
            'template_type'    => 'txt',
            'template_content' => sprintf("{{ %s }}", MassNotificationSender::MAINTENANCE_VARIABLE),
            'template_subject' => $subject,
            'template_criteria' => new EmailTemplateCriteria(Configuration::DEFAULT_MASS_NOTIFICATION_TEMPLATE),
        ];

        $this->manager->expects($this->once())
            ->method('process')
            ->with(
                null,
                $this->callback([$this, 'assertMassNotification']),
                null,
                [MassNotificationSender::MAINTENANCE_VARIABLE => $body]
            );
        $this->templateRepository->expects($this->once())
            ->method('isExist')
            ->with(new EmailTemplateCriteria(self::TEMPLATE_NAME))
            ->willReturn(false);

        self::assertEquals(2, $this->sender->send($body, $subject, $senderEmail, $senderName));
    }

    /**
     * @param array $massNotifications
     * @return bool
     */
    public function assertMassNotification($massNotifications)
    {
        $params = $this->massNotificationParams;

        /** @var TemplateMassNotification $massNotification */
        $massNotification = current($massNotifications);
        self::assertEquals($params['sender_name'], $massNotification->getSenderName());
        self::assertEquals($params['sender_email'], $massNotification->getSenderEmail());
        self::assertEquals($params['template_criteria'], $massNotification->getTemplateCriteria());
        self::assertEquals($params['recipients'], $massNotification->getRecipients());
        self::assertEquals($params['recipient_emails'], $massNotification->getRecipientEmails());

        $template = $massNotification->getTemplate();
        self::assertInstanceOf(EmailTemplateInterface::class, $template);
        self::assertEquals($params['template_type'], $template->getType());
        self::assertEquals($params['template_content'], $template->getContent());
        self::assertEquals($params['template_subject'], $template->getSubject());

        return true;
    }
}
