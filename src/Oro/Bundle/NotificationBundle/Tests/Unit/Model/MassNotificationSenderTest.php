<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
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

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dqlNameFormatter;

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

        $this->dqlNameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter')
            ->disableOriginalConstructor()->getMock();

        $this->processor = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sender = new MassNotificationSender(
            $this->processor,
            $this->cm,
            $this->entityManager,
            $this->entityPool,
            $this->dqlNameFormatter
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
        unset($this->dqlNameFormatter);
    }

    public function testSendToActiveUsersWithEmptySender()
    {
        $body = "Test Body";
        $subject = "Test Subject";
        $userRecipients = [
            ['email' => 'test1@test.com', 'name' => 'test1'],
            ['email' => 'test2@test.com', 'name' => 'test2']
        ];
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
        $template->expects($this->once())->method('getType')->will($this->returnValue('html'));
        $template->expects($this->once())->method('getContent')->will($this->returnValue('test content'));
        $template->expects($this->once())->method('getSubject')->will($this->returnValue('subject'));

        $this->templateRepository->expects($this->once())->method('findByName')->with(self::TEMPLATE_NAME)->will(
            $this->returnValue($template)
        );
        $this->dqlNameFormatter->expects($this->once())->method('getFormattedNameDQL')->will(
            $this->returnValue("ConcatExpression")
        );

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects($this->any())
            ->method('getResult')
            ->will($this->returnValue($userRecipients));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['getQuery', 'andWhere', 'setParameter'])
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('u.enabled = :enabled')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('enabled', true);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $this->userRepository->expects($this->once())->method('getPrimaryEmailsQb')->with("ConcatExpression")->will(
            $this->returnValue($queryBuilder)
        );

        $this->massNotificationParams = [
            'sender_name'      => self::TEST_SENDER_NAME,
            'sender_email'     => self::TEST_SENDER_EMAIL,
            'recipients'       => [['test1@test.com' => 'test1'], ['test2@test.com' => 'test2']],
            'template_type'    => 'html',
            'template_content' => 'test content',
            'template_subject' => $subject
        ];
        $this->processor->expects($this->once())->method('process')->with(
            null,
            $this->callback([$this, 'assertMassNotification']),
            null,
            [MassNotificationSender::MAINTENANCE_VARIABLE => $body]
        );

        $this->processor->expects($this->once())->method('setMessageLimit')->with(0);
        $this->processor->expects($this->once())->method('addLogType')
                    ->with(MassNotificationSender::NOTIFICATION_LOG_TYPE);

        $this->entityPool->expects($this->once())->method('persistAndFlush')->with($this->entityManager);

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

        $this->templateRepository->expects($this->once())->method('findByName')->with(self::TEMPLATE_NAME)->will(
            $this->returnValue(null)
        );
        $this->massNotificationParams = [
            'sender_name'      => $senderName,
            'sender_email'     => $senderEmail,
            'recipients'       => explode(';', $configRecipients),
            'template_type'    => 'txt',
            'template_content' => sprintf("{{ %s }}", MassNotificationSender::MAINTENANCE_VARIABLE),
            'template_subject' => $subject
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
        $this->assertEquals($params['sender_name'], $massNotification->getSenderName());
        $this->assertEquals($params['sender_email'], $massNotification->getSenderEmail());
        $this->assertEquals($params['recipients'], $massNotification->getRecipientEmails());
        $this->assertTrue($template instanceof EmailTemplateInterface);
        $this->assertEquals($params['template_type'], $template->getType());
        $this->assertEquals($params['template_content'], $template->getContent());
        $this->assertEquals($params['template_subject'], $template->getSubject());

        return true;
    }
}
