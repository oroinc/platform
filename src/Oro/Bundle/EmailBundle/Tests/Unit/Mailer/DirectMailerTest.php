<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DirectMailerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Swift_Mailer */
    protected $baseMailer;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EmailOrigin */
    protected $emailOrigin;

    protected function setUp()
    {
        $this->baseMailer = $this->getMailerMock();
        $this->container  = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->emailOrigin =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailOrigin')
                ->disableOriginalConstructor()
                ->getMock();

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
        $this->container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    // 1 = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE
                    [
                        ['swiftmailer.mailer.test1', 1, $this->getMailerMock()],
                        ['swiftmailer.mailer.test2', 1, $this->baseMailer],
                        ['swiftmailer.mailer.test2.transport.real', 1, $realTransport],
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

        $this->container->expects($this->once())
            ->method('get')
            ->willReturn($dispatcher);

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
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMailerMock()
    {
        return $this->getMockBuilder('\Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Check that SMTP transport initialized correctly and not overridden
     */
    public function testAfterPrepareSmtpTransportForEsmtpTransport()
    {
        /** @var \Swift_Transport_EsmtpTransport|\PHPUnit_Framework_MockObject_MockObject $smtpTransportMock */
        $smtpTransportMock = $this->getMockBuilder(\Swift_Transport_EsmtpTransport::class)
            ->disableOriginalConstructor()
            ->setMethods(['setHost', 'setPort', 'setEncryption', 'setUsername', 'setPassword'])
            ->getMock();
        $streamOptions = ['ssl' => ['verify_peer' => false]];
        $smtpTransportMock->setStreamOptions($streamOptions);

        $this->baseMailer->expects($this->once())
            ->method('getTransport')
            ->willReturn($smtpTransportMock);

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
        /** @var \Swift_Transport_AbstractSmtpTransport|\PHPUnit_Framework_MockObject_MockObject $smtpTransportMock */
        $smtpTransportMock = $this->getMockBuilder(\Swift_Transport_AbstractSmtpTransport::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->baseMailer->expects($this->once())
            ->method('getTransport')
            ->willReturn($smtpTransportMock);

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
