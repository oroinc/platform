<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer;

use Oro\Bundle\EmailBundle\Mailer\DirectMailer;

class DirectMailerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $baseMailer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $userEmailOrigin;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $imapEmailGoogleOauth2Manager;

    protected function setUp()
    {
        $this->baseMailer = $this->getMailerMock();
        $this->container  = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->userEmailOrigin =
            $this->getMockBuilder('Oro\Bundle\ImapBundle\Entity\UserEmailOrigin')
                ->disableOriginalConstructor()
                ->getMock();
        $this->userEmailOrigin->expects($this->any())
            ->method('getSmtpHost')
            ->will($this->returnValue('smtp.gmail.com'));
        $this->userEmailOrigin->expects($this->any())
            ->method('getSmtpPort')
            ->will($this->returnValue(465));
        $this->userEmailOrigin->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue('user1'));

        $managerClass = 'Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOauth2Manager';
        $this->imapEmailGoogleOauth2Manager = $this->getMockBuilder($managerClass)
            ->disableOriginalConstructor()
            ->setMethods(['getAccessTokenWithCheckingExpiration'])
            ->getMock();
    }

    public function testSendNonSpooled()
    {
        $message          = new \Swift_Message();
        $failedRecipients = array();
        $transport        = $this->getMock('\Swift_Transport');

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

        $mailer = new DirectMailer($this->baseMailer, $this->container, $this->imapEmailGoogleOauth2Manager);
        $this->assertEquals(1, $mailer->send($message, $failedRecipients));
    }

    public function testSendSpooled()
    {
        $message          = new \Swift_Message();
        $failedRecipients = array();
        $transport        = $this->getMockBuilder('\Swift_Transport_SpoolTransport')
            ->disableOriginalConstructor()
            ->getMock();
        $realTransport    = $this->getMock('\Swift_Transport');

        $this->baseMailer->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($transport));

        $this->container->expects($this->once())
            ->method('getParameter')
            ->with('swiftmailer.mailers')
            ->will($this->returnValue(array('test1' => null, 'test2' => null)));
        $this->container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    // 1 = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE
                    array(
                        array('swiftmailer.mailer.test1', 1, $this->getMailerMock()),
                        array('swiftmailer.mailer.test2', 1, $this->baseMailer),
                        array('swiftmailer.mailer.test2.transport.real', 1, $realTransport),
                    )
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

        $mailer = new DirectMailer($this->baseMailer, $this->container, $this->imapEmailGoogleOauth2Manager);
        $this->assertEquals(1, $mailer->send($message, $failedRecipients));
    }

    /**
     * @expectedException \Exception
     */
    public function testSendWithException()
    {
        $message          = new \Swift_Message();
        $failedRecipients = array();
        $transport        = $this->getMock('\Swift_Transport');

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

        $mailer = new DirectMailer($this->baseMailer, $this->container, $this->imapEmailGoogleOauth2Manager);
        $this->assertEquals(1, $mailer->send($message, $failedRecipients));
    }

    public function testSendWithSmtpConfigured()
    {
        $message          = new \Swift_Message();
        $failedRecipients = array();
        $transport        = $this->getMock('\Swift_SmtpTransport');

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

        $encoder = $this->getMock('Oro\Bundle\SecurityBundle\Encoder\Mcrypt');
        $this->container->expects($this->any())
            ->method('get')
            ->with($this->equalTo('oro_security.encoder.mcrypt'))
            ->will($this->returnValue($encoder));

        $mailer = new DirectMailer($this->baseMailer, $this->container, $this->imapEmailGoogleOauth2Manager);

        $transport->expects($this->once())->method('setHost');
        $transport->expects($this->once())->method('setPort');

        $mailer->prepareSmtpTransport($this->userEmailOrigin);

        $this->assertEquals(1, $mailer->send($message, $failedRecipients));
    }

    public function testCreateSmtpTransport()
    {
        $transport = $this->getMock('\Swift_Transport');

        $this->baseMailer->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($transport));
        $this->container->expects($this->never())
            ->method('getParameter');

        $encoder = $this->getMock('Oro\Bundle\SecurityBundle\Encoder\Mcrypt');
        $this->container->expects($this->any())
            ->method('get')
            ->with($this->equalTo('oro_security.encoder.mcrypt'))
            ->will($this->returnValue($encoder));

        $mailer = new DirectMailer($this->baseMailer, $this->container, $this->imapEmailGoogleOauth2Manager);

        $transport->expects($this->never())->method('setHost');
        $transport->expects($this->never())->method('setPort');

        $mailer->prepareSmtpTransport($this->userEmailOrigin);
        $smtpTransport = $mailer->getTransport();

        $this->assertInstanceOf('\Swift_SmtpTransport', $smtpTransport);
    }

    /**
     * @expectedException \Oro\Bundle\EmailBundle\Exception\NotSupportedException
     */
    public function testRegisterPlugin()
    {
        $transport = $this->getMock('\Swift_Transport');

        $this->baseMailer->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($transport));

        $mailer = new DirectMailer($this->baseMailer, $this->container, $this->imapEmailGoogleOauth2Manager);
        $plugin = $this->getMock('\Swift_Events_EventListener');
        $mailer->registerPlugin($plugin);
    }

    protected function getMailerMock()
    {
        return $this->getMockBuilder('\Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
