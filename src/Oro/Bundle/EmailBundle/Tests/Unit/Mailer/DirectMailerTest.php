<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\EmailBundle\Provider\SmtpSettingsAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DirectMailerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|\Swift_Mailer */
    protected $baseMailer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    protected $container;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EmailOrigin */
    protected $emailOrigin;

    protected function setUp()
    {
        $this->baseMailer = $this->getMailerMock();
        $this->container  = $this->createMock(ContainerInterface::class);
        $this->emailOrigin = $this->createMock(EmailOrigin::class);

        $managerClass = 'Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOauth2Manager';
        $this->imapEmailGoogleOauth2Manager = $this->getMockBuilder($managerClass)
            ->disableOriginalConstructor()
            ->setMethods(['getAccessTokenWithCheckingExpiration'])
            ->getMock();
    }

    public function testSendNonSpooled()
    {
        $message          = new \Swift_Message();
        $failedRecipients = [];
        $transport        = $this->createMock('\Swift_Transport');

        $this->baseMailer->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($transport));
        $this->container->expects($this->never())
            ->method('getParameter');

        $smtpSettingsProvider = $this->createMock(SmtpSettingsAwareInterface::class);
        $smtpSettingsProvider->expects($this->any())
            ->method('getSmtpSettings')
            ->willReturn(new SmtpSettings());

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($smtpSettingsProvider);

        $transport->expects($this->at(0))
            ->method('isStarted')
            ->will($this->returnValue(false));
        $transport->expects($this->once())
            ->method('start');
        $transport->expects($this->at(2))
            ->method('isStarted')
            ->will($this->returnValue(true));
        $transport->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($message), $this->identicalTo($failedRecipients))
            ->will($this->returnValue(1));
        $transport->expects($this->once())
            ->method('stop');

        $mailer = new DirectMailer($this->baseMailer, $this->container);
        $this->assertEquals(1, $mailer->send($message, $failedRecipients));
    }

    public function testSendSpooled()
    {
        $message          = new \Swift_Message();
        $failedRecipients = [];
        $transport        = $this->getMockBuilder('\Swift_Transport_SpoolTransport')
            ->disableOriginalConstructor()
            ->getMock();
        $realTransport    = $this->createMock('\Swift_Transport');

        $this->baseMailer->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($transport));

        $this->container->expects($this->once())
            ->method('getParameter')
            ->with('swiftmailer.mailers')
            ->will($this->returnValue(['test1' => null, 'test2' => null]));

        $smtpSettingsProvider = $this->createMock(SmtpSettingsAwareInterface::class);
        $smtpSettingsProvider->expects($this->any())
            ->method('getSmtpSettings')
            ->willReturn(new SmtpSettings());

        $this->container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    // 1 = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE
                    [
                        ['swiftmailer.mailer.test1', 1, $this->getMailerMock()],
                        ['swiftmailer.mailer.test2', 1, $this->baseMailer],
                        ['swiftmailer.mailer.test2.transport.real', 1, $realTransport],
                        ['oro_email.provider.smtp_settings', 1, $smtpSettingsProvider],
                    ]
                )
            );

        $realTransport->expects($this->at(0))
            ->method('isStarted')
            ->will($this->returnValue(false));
        $realTransport->expects($this->once())
            ->method('start');
        $realTransport->expects($this->at(2))
            ->method('isStarted')
            ->will($this->returnValue(true));
        $realTransport->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($message), $this->identicalTo($failedRecipients))
            ->will($this->returnValue(1));
        $realTransport->expects($this->once())
            ->method('stop');

        $this->container
            ->expects($this->any())
            ->method('initialized')
            ->willReturnMap([
                ['swiftmailer.mailer.test1', true],
                ['swiftmailer.mailer.test2', true]
            ]);

        $mailer = new DirectMailer($this->baseMailer, $this->container);
        $this->assertEquals(1, $mailer->send($message, $failedRecipients));
    }

    public function testSendSpooledWithSmtpSettings()
    {
        $message = new \Swift_Message();
        $failedRecipients = [];

        $this->baseMailer->expects($this->once())
            ->method('getTransport')
            ->willReturn($this->createMock(\Swift_Transport_SpoolTransport::class));

        $this->container->expects($this->once())
            ->method('getParameter')
            ->with('swiftmailer.mailers')
            ->willReturn(['test1' => null, 'test2' => null]);

        $smtpSettingsProvider = $this->createMock(SmtpSettingsAwareInterface::class);
        $smtpSettingsProvider->expects($this->any())
            ->method('getSmtpSettings')
            ->willReturn(new SmtpSettings('127.0.0.1', 1025, ''));

        $realTransport = $this->createMock(\Swift_Transport_EsmtpTransport::class);
        $realTransport->expects($this->at(0))
            ->method('isStarted')
            ->willReturn(false);

        $realTransport->expects($this->once())
            ->method('setHost')
            ->with('127.0.0.1')
            ->willReturnSelf();
        $realTransport->expects($this->once())
            ->method('setPort')
            ->with(1025)
            ->willReturnSelf();
        $realTransport->expects($this->once())
            ->method('setEncryption')
            ->with('')
            ->willReturnSelf();
        // to process auth settings Swift will use AuthHandler which will called by __call() method
        $realTransport->expects($this->at(3))
            ->method('__call')
            ->with('setUsername', [''])
            ->willReturnSelf();
        $realTransport->expects($this->at(4))
            ->method('__call')
            ->with('setPassword', [''])
            ->willReturnSelf();

        $realTransport->expects($this->once())
            ->method('start');
        $realTransport->expects($this->at(2))
            ->method('isStarted')
            ->willReturn(true);
        $realTransport->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($message), $this->identicalTo($failedRecipients))
            ->willReturn(1);
        $realTransport->expects($this->once())
            ->method('stop');

        $this->container->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    [
                        'swiftmailer.mailer.test1',
                        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                        $this->getMailerMock()
                    ],
                    [
                        'swiftmailer.mailer.test2',
                        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                        $this->baseMailer
                    ],
                    [
                        'swiftmailer.mailer.test2.transport.real',
                        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                        $realTransport
                    ],
                    [
                        'oro_email.provider.smtp_settings',
                        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                        $smtpSettingsProvider
                    ],
                ]
            );

        $this->container
            ->expects($this->any())
            ->method('initialized')
            ->willReturnMap([
                ['swiftmailer.mailer.test1', true],
                ['swiftmailer.mailer.test2', true]
            ]);

        $mailer = new DirectMailer($this->baseMailer, $this->container);
        $this->assertEquals(1, $mailer->send($message, $failedRecipients));
    }

    /**
     * @expectedException \Exception
     */
    public function testSendWithException()
    {
        $message          = new \Swift_Message();
        $failedRecipients = [];
        $transport        = $this->createMock('\Swift_Transport');

        $this->baseMailer->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($transport));
        $this->container->expects($this->never())
            ->method('getParameter');

        $smtpSettingsProvider = $this->createMock(SmtpSettingsAwareInterface::class);
        $smtpSettingsProvider->expects($this->any())
            ->method('getSmtpSettings')
            ->willReturn(new SmtpSettings());

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($smtpSettingsProvider);

        $transport->expects($this->at(0))
            ->method('isStarted')
            ->will($this->returnValue(false));
        $transport->expects($this->once())
            ->method('start');
        $transport->expects($this->at(2))
            ->method('isStarted')
            ->will($this->returnValue(true));
        $transport->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($message), $this->identicalTo($failedRecipients))
            ->will($this->throwException(new \Exception('test failure')));
        $transport->expects($this->once())
            ->method('stop');

        $mailer = new DirectMailer($this->baseMailer, $this->container);
        $this->assertEquals(1, $mailer->send($message, $failedRecipients));
    }

    public function testCreateSmtpTransport()
    {
        $transportMock = $this->createMock('\Swift_Transport');
        $smtpTransportMock = $this->getMockBuilder('\Swift_Transport_EsmtpTransport')
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getMockBuilder('Oro\Bundle\EmailBundle\Event\SendEmailTransport')
            ->disableOriginalConstructor()
            ->getMock();
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturn($event);
        $event->expects($this->once())
            ->method('getTransport')
            ->willReturn($smtpTransportMock);

        $this->baseMailer->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($transportMock));

        $smtpSettingsProvider = $this->createMock(SmtpSettingsAwareInterface::class);
        $smtpSettingsProvider->expects($this->any())
            ->method('getSmtpSettings')
            ->willReturn(new SmtpSettings());

        $this->container->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['event_dispatcher', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $dispatcher],
                    [
                        'oro_email.provider.smtp_settings',
                        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                        $smtpSettingsProvider
                    ],
                ]
            );

        $mailer = new DirectMailer($this->baseMailer, $this->container);
        $mailer->prepareEmailOriginSmtpTransport($this->emailOrigin);
        $smtpTransport = $mailer->getTransport();

        $this->assertInstanceOf('\Swift_Transport_EsmtpTransport', $smtpTransport);
    }

    /**
     * @expectedException \Oro\Bundle\EmailBundle\Exception\NotSupportedException
     */
    public function testRegisterPlugin()
    {
        $mailer = new DirectMailer($this->baseMailer, $this->container);
        $plugin = $this->createMock('\Swift_Events_EventListener');
        $mailer->registerPlugin($plugin);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMailerMock()
    {
        return $this->createMock('\Swift_Mailer');
    }

    /**
     * Check that SMTP transport initialized correctly and not overridden
     */
    public function testAfterPrepareSmtpTransportForEsmtpTransport()
    {
        /** @var \Swift_Transport_EsmtpTransport|\PHPUnit\Framework\MockObject\MockObject $smtpTransportMock */
        $smtpTransportMock = $this->getMockBuilder(\Swift_Transport_EsmtpTransport::class)
            ->disableOriginalConstructor()
            ->setMethods(['setHost', 'setPort', 'setEncryption', 'setUsername', 'setPassword'])
            ->getMock();
        $streamOptions = ['ssl' => ['verify_peer' => false]];
        $smtpTransportMock->setStreamOptions($streamOptions);

        $this->baseMailer->expects($this->once())
            ->method('getTransport')
            ->willReturn($smtpTransportMock);

        $smtpSettingsProvider = $this->createMock(SmtpSettingsAwareInterface::class);
        $smtpSettingsProvider->expects($this->any())
            ->method('getSmtpSettings')
            ->willReturn(new SmtpSettings());

        $this->container->expects($this->any())
            ->method('get')
            ->with('oro_email.provider.smtp_settings', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
            ->willReturn($smtpSettingsProvider);

        $settings = new SmtpSettings();
        $settings->setHost('test.host');
        $settings->setPort(442);
        $settings->setUsername('username');
        $settings->setPassword('pass');
        $settings->setEncryption('tls');

        $smtpTransportMock->expects($this->once())
            ->method('setHost')
            ->with($settings->getHost())
            ->willReturnSelf();
        $smtpTransportMock->expects($this->once())
            ->method('setPort')
            ->with($settings->getPort())
            ->willReturnSelf();
        $smtpTransportMock->expects($this->once())
            ->method('setEncryption')
            ->with($settings->getEncryption())
            ->willReturnSelf();
        $smtpTransportMock->expects($this->once())
            ->method('setUsername')
            ->with($settings->getUsername())
            ->willReturnSelf();
        $smtpTransportMock->expects($this->once())
            ->method('setPassword')
            ->with($settings->getPassword())
            ->willReturnSelf();

        $mailer = new DirectMailer($this->baseMailer, $this->container);
        $mailer->afterPrepareSmtpTransport($settings);

        $this->assertSame($streamOptions, $smtpTransportMock->getStreamOptions());
    }

    /**
     * Check that SMTP transport initialized correctly new instance created
     */
    public function testAfterPrepareSmtpTransportForNonEsmtpTransport()
    {
        /** @var \Swift_Transport_AbstractSmtpTransport|\PHPUnit\Framework\MockObject\MockObject $smtpTransportMock */
        $smtpTransportMock = $this->getMockBuilder(\Swift_Transport_AbstractSmtpTransport::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->baseMailer->expects($this->once())
            ->method('getTransport')
            ->willReturn($smtpTransportMock);

        $smtpSettingsProvider = $this->createMock(SmtpSettingsAwareInterface::class);
        $smtpSettingsProvider->expects($this->any())
            ->method('getSmtpSettings')
            ->willReturn(new SmtpSettings());

        $this->container->expects($this->any())
            ->method('get')
            ->with('oro_email.provider.smtp_settings', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
            ->willReturn($smtpSettingsProvider);

        $settings = new SmtpSettings();
        $settings->setHost('test.host');
        $settings->setPort(442);
        $settings->setUsername('username');
        $settings->setPassword('pass');
        $settings->setEncryption('tls');

        $smtpTransportMock->expects($this->never())
            ->method($this->anything());

        $mailer = new DirectMailer($this->baseMailer, $this->container);
        $mailer->afterPrepareSmtpTransport($settings);
        $this->assertAttributeInstanceOf(\Swift_SmtpTransport::class, 'smtpTransport', $mailer);
        $transport = $mailer->getTransport();

        $this->assertEquals($settings->getHost(), $transport->getHost());
        $this->assertEquals($settings->getPort(), $transport->getPort());
        $this->assertEquals($settings->getEncryption(), $transport->getEncryption());
        $this->assertEquals($settings->getUsername(), $transport->getUsername());
        $this->assertEquals($settings->getPassword(), $transport->getPassword());
    }
}
