<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor;
use Oro\Bundle\NotificationBundle\Model\MassNotification;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;

class MassNotificationSenderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_SENDER_EMAIL = 'admin@example.com';
    const TEST_SENDER_NAME  = 'sender name';
    const TEMPLATE_NAME     = 'test template';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $userRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $templateRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityPool;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager */
    protected $cm;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EmailNotificationProcessor */
    protected $processor;

    /** @var MassNotificationSender */
    protected $sender;

    /** @var  array */
    protected $massNotificationParams;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->setMethods(['getRepository'])->getMock();

        $this->userRepository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
                    ->disableOriginalConstructor()->getMock();

        $this->templateRepository =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository')
                    ->disableOriginalConstructor()->getMock();

        $this->entityPool = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Doctrine\EntityPool')
            ->disableOriginalConstructor()->getMock();

        $this->cm = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->processor = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor->expects($this->once())->method('setMessageLimit')->with(0);

        $this->processor->expects($this->once())->method('addLogEntity')
            ->with('Oro\Bundle\NotificationBundle\Entity\MassNotification');

        $this->entityPool->expects($this->any())->method('persistAndFlush')->with($this->entityManager);

        $this->sender = new MassNotificationSender(
            $this->processor,
            $this->cm,
            $this->entityManager,
            $this->entityPool
        );
    }

    protected function tearDown()
    {
        unset($this->entityManager);
        unset($this->cm);
        unset($this->entityPool);
        unset($this->processor);
        unset($this->sender);
        unset($this->templateRepository);
        unset($this->userRepository);
    }

    public function testSendToActiveUsersWithEmptySender()
    {
        $body = "Test Body";
        $subject = "Test Subject";
        $userRecipients = ['test1@test.com', 'test2@test.com'];
        $this->cm->expects($this->any())->method('get')->will(
            $this->returnValueMap(
                [
                    ['oro_notification.email_notification_sender_email', false, false, self::TEST_SENDER_EMAIL],
                    ['oro_notification.email_notification_sender_name', false, false, self::TEST_SENDER_NAME],
                    ['oro_notification.mass_notification_recipients', false, false, ''],
                    ['oro_notification.mass_notification_template', false, false, self::TEMPLATE_NAME]
                ]
            )
        );

        $this->entityManager->expects($this->at(1))->method('getRepository')->with('OroEmailBundle:EmailTemplate')
                            ->will($this->returnValue($this->templateRepository));

        $this->entityManager->expects($this->at(0))->method('getRepository')->with('OroUserBundle:User')->will(
            $this->returnValue($this->userRepository)
        );

        $template = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailTemplate');
        $template->expects($this->once())->method('setSubject')->with($subject);
        $this->templateRepository->expects($this->once())->method('findByName')->with(self::TEMPLATE_NAME)->will(
            $this->returnValue($template)
        );

        $this->userRepository->expects($this->once())->method('getActiveUserEmails')->will(
            $this->returnValue($userRecipients)
        );

        $this->massNotificationParams = [
            'sender_name'   => self::TEST_SENDER_NAME,
            'sender_email'  => self::TEST_SENDER_EMAIL,
            'recipients'    => $userRecipients,
            'template_type' => null
        ];
        $this->processor->expects($this->once())->method('process')->with(
            null,
            $this->callback([$this, 'assertMassNotification']),
            null,
            [MassNotificationSender::MAINTENANCE_VARIABLE => $body]
        );

        $this->assertEquals(2, $this->sender->send($body, $subject));
    }

    public function testSendToConfigEmailsWithEmtpyTemplate()
    {
        $body = "Test Body";
        $subject = null;
        $senderName = "Sender Name";
        $senderEmail = "sender@test.com";
        $configRecipients = 'test1@test.com;test2@test.com';
        $this->cm->expects($this->any())->method('get')->will(
            $this->returnValueMap(
                [
                    ['oro_notification.mass_notification_recipients', false, false, $configRecipients],
                    ['oro_notification.mass_notification_template', false, false, self::TEMPLATE_NAME]
                ]
            )
        );

        $this->entityManager->expects($this->at(0))->method('getRepository')->with('OroEmailBundle:EmailTemplate')
                            ->will($this->returnValue($this->templateRepository));

        $template = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailTemplate');
        $this->templateRepository->expects($this->once())->method('findByName')->with(self::TEMPLATE_NAME)->will(
            $this->returnValue(null)
        );
        $this->massNotificationParams = [
            'sender_name'   => $senderName,
            'sender_email'  => $senderEmail,
            'recipients'    => explode(';', $configRecipients),
            'template_type' => 'txt'
        ];
        $this->processor->expects($this->once())->method('process')->with(
            null,
            $this->callback([$this, 'assertMassNotification']),
            null,
            [MassNotificationSender::MAINTENANCE_VARIABLE => $body]
        );

        $this->assertEquals(2, $this->sender->send($body, $subject, $senderEmail, $senderName));
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
        $template = $massNotification->getTemplate();
        $result = $massNotification->getSenderName() == $params['sender_name'] &&
            $massNotification->getSenderEmail() == $params['sender_email'] &&
            $massNotification->getRecipientEmails() == $params['recipients'] &&
            $template instanceof EmailTemplate &&
            $template->getType() == $params['template_type'];

        return $result;
    }
}
