<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Processor;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor;

class EmailNotificationProcessorTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = 'SomeEntity';

    const TEST_SENDER_EMAIL = 'admin@example.com';
    const TEST_SENDER_NAME  = 'asdSDA';

    const TEST_ENV = 'prod';

    const TEST_MESSAGE_LIMIT = 10;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityPool;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $emailRenderer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $twig;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mailer;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager */
    protected $cm;

    /** @var EmailNotificationProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->logger = $this->getMock('Psr\Log\LoggerInterface');

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $this->entityPool = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Doctrine\EntityPool')
            ->disableOriginalConstructor()->getMock();

        $this->emailRenderer = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRenderer')
            ->disableOriginalConstructor()->getMock();

        $this->mailer = $this->getMockBuilder('Swift_Mailer')
            ->disableOriginalConstructor()->getMock();

        $this->cm = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->cm->expects($this->any())->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['oro_notification.email_notification_sender_email', false, false, self::TEST_SENDER_EMAIL],
                        ['oro_notification.email_notification_sender_name', false, false, self::TEST_SENDER_NAME]
                    ]
                )
            );

        $emailProcessor = $this->getMockBuilder('Oro\Bundle\EmailBundle\Mailer\Processor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new EmailNotificationProcessor(
            $this->logger,
            $this->entityManager,
            $this->entityPool,
            $this->emailRenderer,
            $this->mailer,
            $this->cm,
            $emailProcessor
        );

        $this->processor->setEnv(self::TEST_ENV);
        $this->processor->setMessageLimit(self::TEST_MESSAGE_LIMIT);
    }

    protected function tearDown()
    {
        unset($this->entityManager);
        unset($this->twig);
        unset($this->securityPolicy);
        unset($this->sandbox);
        unset($this->mailer);
        unset($this->logger);
        unset($this->securityContext);
        unset($this->configProvider);
        unset($this->cache);
        unset($this->processor);
        unset($this->cm);
    }

    /**
     * @dataProvider processDataProvider
     * @param array $data
     * @param array $expected
     */
    public function testProcess(array $data, array $expected)
    {
        $object       = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $template = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailTemplate');
        $template->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('html'));

        if ($data['senderAware']) {
            $notification = $this->getMock(
                'Oro\Bundle\NotificationBundle\Processor\SenderAwareEmailNotificationInterface'
            );
            if ($data['fromEmail']) {
                $notification->expects($this->once())
                    ->method('getSenderName')
                    ->will($this->returnValue($data['fromName']));
                $notification->expects($this->exactly(2))
                    ->method('getSenderEmail')
                    ->will($this->returnValue($data['fromEmail']));
            } else {
                $notification->expects($this->once())
                    ->method('getSenderEmail')
                    ->will($this->returnValue(null));
            }
        } else {
            $notification = $this->getMock('Oro\Bundle\NotificationBundle\Processor\EmailNotificationInterface');
        }

        $notification->expects($this->once())
            ->method('getTemplate')
            ->will($this->returnValue($template));

        $this->emailRenderer->expects($this->once())
            ->method('compileMessage')
            ->with($template, array('entity' => $object))
            ->will($this->returnValue(array($data['subject'], $data['body'])));

        $notification->expects($this->once())
            ->method('getRecipientEmails')
            ->will($this->returnValue(array($data['to'])));

        $assertCallWithExpectedMessage = function ($message) use ($expected) {
            /** @var \Swift_Message $message */
            $this->isInstanceOf('Swift_Message', $message);
            $this->assertEquals($expected['subject'], $message->getSubject());
            $this->assertEquals($expected['body'], $message->getBody());
            $this->assertEquals(
                array($expected['fromEmail'] => $expected['fromName']),
                $message->getFrom()
            );
            $this->assertEquals(array($expected['to'] => null), $message->getTo());
            $this->assertEquals('text/html', $message->getContentType());

            return true;
        };
        $this->mailer->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(
                    $assertCallWithExpectedMessage
                )
            );

        $this->expectAddJob();

        $this->processor->process($object, array($notification));
    }

    public function processDataProvider()
    {
        return [
            'Process Notification implement EmailNotificationInterface' => [
                'data' => [
                    'subject' => 'subject',
                    'body' => 'body',
                    'to' => 'recipient@example.com',
                    'senderAware' => false
                ],
                'expected' => [
                    'subject' => 'subject',
                    'body' => 'body',
                    'to' => 'recipient@example.com',
                    'fromName' => self::TEST_SENDER_NAME,
                    'fromEmail' => self::TEST_SENDER_EMAIL
                ]
            ],
            'Process Notification implement SenderAwareEmailNotificationInterface' => [
                'data' => [
                    'subject' => 'subject',
                    'body' => 'body',
                    'to' => 'recipient@example.com',
                    'fromName' => 'test sender',
                    'fromEmail' => 'test_sender@mail.com',
                    'senderAware' => true
                ],
                'expected' => [
                    'subject' => 'subject',
                    'body' => 'body',
                    'to' => 'recipient@example.com',
                    'fromName' => 'test sender',
                    'fromEmail' => 'test_sender@mail.com'
                ]
            ],
            'Process Notification implement SenderAwareEmailNotificationInterface but sender email is empty' => [
                'data' => [
                    'subject' => 'subject',
                    'body' => 'body',
                    'to' => 'recipient@example.com',
                    'fromName' => null,
                    'fromEmail' => null,
                    'senderAware' => true
                ],
                'expected' => [
                    'subject' => 'subject',
                    'body' => 'body',
                    'to' => 'recipient@example.com',
                    'fromName' => self::TEST_SENDER_NAME,
                    'fromEmail' => self::TEST_SENDER_EMAIL
                ]
            ],
        ];
    }

    public function testAddLogType()
    {
        $spool = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Provider\Mailer\DbSpool')
                    ->disableOriginalConstructor()->getMock();
        $spool->expects($this->once())->method('setLogType')->with('test type');
        $tranport = $this->getMockBuilder('Swift_Transport_SpoolTransport')
                    ->disableOriginalConstructor()->getMock();
        $tranport->expects($this->once())->method('getSpool')->will(
            $this->returnValue($spool)
        );
        $this->mailer->expects($this->once())->method('getTransport')->will(
            $this->returnValue($tranport)
        );
        $this->processor->addLogType('test type');
    }

    /**
     * Test processor with exception and empty recipients
     */
    public function testProcessErrors()
    {
        $object        = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $notification  = $this->getMock('Oro\Bundle\NotificationBundle\Processor\EmailNotificationInterface');
        $notifications = array($notification);

        $template = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailTemplate');
        $notification->expects($this->once())
            ->method('getTemplate')
            ->will($this->returnValue($template));

        $this->emailRenderer->expects($this->once())
            ->method('compileMessage')
            ->will($this->throwException(new \Twig_Error('bla bla bla')));

        $this->logger->expects($this->once())
            ->method('error');

        $notification->expects($this->never())
            ->method('getRecipientEmails');

        $this->mailer->expects($this->never())
            ->method('send');

        $this->processor->process($object, $notifications);

        $this->entityPool->expects($this->never())
            ->method($this->anything());
    }

    /**
     * Add job assertions
     */
    protected function expectAddJob()
    {
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getSingleScalarResult'))
            ->getMockForAbstractClass();

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilder->expects($this->at(0))
            ->method('select')
            ->with('COUNT(job)')
            ->will($this->returnSelf());

        $queryBuilder->expects($this->at(1))
            ->method('from')
            ->with('JMSJobQueueBundle:Job', 'job')
            ->will($this->returnSelf());

        $queryBuilder->expects($this->at(2))
            ->method('where')
            ->with('job.command = :command AND job.state IN ( :state )')
            ->will($this->returnSelf());

        $queryBuilder->expects($this->at(3))
            ->method('setParameters')
            ->with(
                [
                    'command' => EmailNotificationProcessor::SEND_COMMAND,
                    'state'   => [Job::STATE_RUNNING, Job::STATE_PENDING]
                ]
            )
            ->will($this->returnSelf());

        $queryBuilder->expects($this->at(4))
            ->method('getQuery')
            ->will($this->returnValue($query));

        $query->expects($this->once())->method('getSingleScalarResult')
            ->will($this->returnValue('0'));

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->entityPool->expects($this->once())
            ->method('addPersistEntity')
            ->with(
                $this->callback(
                    function ($job) {
                        /** @var Job $job */
                        $this->assertInstanceOf('JMS\JobQueueBundle\Entity\Job', $job);
                        $this->assertEquals(EmailNotificationProcessor::SEND_COMMAND, $job->getCommand());
                        $this->assertEquals(
                            array(
                                '--message-limit=' . self::TEST_MESSAGE_LIMIT,
                                '--env=' . self::TEST_ENV,
                                '--mailer=db_spool_mailer'
                            ),
                            $job->getArgs()
                        );
                        return true;
                    }
                )
            );
    }
}
