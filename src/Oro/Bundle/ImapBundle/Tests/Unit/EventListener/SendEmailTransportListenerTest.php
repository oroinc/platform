<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Event\SendEmailTransport;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\EventListener\SendEmailTransportListener;
use Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOauth2Manager;

class SendEmailTransportListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|UserEmailOrigin */
    protected $userEmailOrigin;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ImapEmailGoogleOauth2Manager */
    protected $imapEmailGoogleOauth2Manager;

    protected function setUp()
    {
        $this->userEmailOrigin =
            $this->getMockBuilder('Oro\Bundle\ImapBundle\Entity\UserEmailOrigin')
                ->disableOriginalConstructor()
                ->getMock();

        $managerClass = 'Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOauth2Manager';
        $this->imapEmailGoogleOauth2Manager = $this->getMockBuilder($managerClass)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider transportDataProvider
     */
    public function testSendWithSmtpConfigured($host, $port, $username, $encryption, $authMode, $password)
    {
        /** @var \Swift_Transport_EsmtpTransport|\PHPUnit_Framework_MockObject_MockObject $smtpTransportMock */
        $smtpTransportMock = $this->getMockBuilder(\Swift_Transport_EsmtpTransport::class)
            ->disableOriginalConstructor()
            ->setMethods(['setHost', 'setPort', 'setEncryption', 'setUsername', 'setPassword', 'setAuthMode'])
            ->getMock();
        $streamOptions = ['ssl' => ['verify_peer' => false]];
        $smtpTransportMock->setStreamOptions($streamOptions);

        $smtpTransportMock->expects($this->once())
            ->method('setHost')
            ->with($host)
            ->willReturnSelf();
        $smtpTransportMock->expects($this->once())
            ->method('setEncryption')
            ->with($encryption)
            ->willReturnSelf();
        $smtpTransportMock->expects($this->once())
            ->method('setPort')
            ->with($port)
            ->willReturnSelf();
        $smtpTransportMock->expects($this->once())
            ->method('setUsername')
            ->with($username)
            ->willReturnSelf();

        $encoder = $this->getEncoderMock($password);
        if ($authMode) {
            $this->imapEmailGoogleOauth2Manager
                ->expects($this->once())
                ->method('getAccessTokenWithCheckingExpiration')
                ->willReturn('test');
            $smtpTransportMock->expects($this->once())
                ->method('setAuthMode')
                ->with($authMode)
                ->willReturnSelf();
        } else {
            $smtpTransportMock->expects($this->once())
                ->method('setPassword')
                ->with($password)
                ->willReturnSelf();
        }
        $sendEmailTransportListener = new SendEmailTransportListener($encoder, $this->imapEmailGoogleOauth2Manager);

        $this->prepareUserEmailOriginMock($host, $port, $username, $encryption);
        $event = $this->prepareEventMock($smtpTransportMock);

        $sendEmailTransportListener->setSmtpTransport($event);
        $this->assertSame($streamOptions, $smtpTransportMock->getStreamOptions());
    }

    public function testNewTransportInstanceCreatedInSetSmtpTransport()
    {
        $host = 'host';
        $port = 442;
        $encryption = 'tls';
        $username = 'test';
        $password = 'pass';

        /** @var \Swift_Transport_AbstractSmtpTransport|\PHPUnit_Framework_MockObject_MockObject $smtpTransportMock */
        $smtpTransportMock = $this->getMockBuilder(\Swift_Transport_AbstractSmtpTransport::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $encoder = $this->getEncoderMock($password);
        $sendEmailTransportListener = new SendEmailTransportListener($encoder, $this->imapEmailGoogleOauth2Manager);

        $this->prepareUserEmailOriginMock($host, $port, $username, $encryption);

        $event = new SendEmailTransport($this->userEmailOrigin, $smtpTransportMock);
        $sendEmailTransportListener->setSmtpTransport($event);

        $transport = $event->getTransport();
        $this->assertInstanceOf(\Swift_SmtpTransport::class, $transport);
        $this->assertEquals($host, $transport->getHost());
        $this->assertEquals($port, $transport->getPort());
        $this->assertEquals($encryption, $transport->getEncryption());
        $this->assertEquals($username, $transport->getUsername());
        $this->assertEquals($password, $transport->getPassword());
    }

    /**
     * @param $password
     * @return \Oro\Bundle\SecurityBundle\Encoder\Mcrypt|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEncoderMock($password)
    {
        $encoder = $this->createMock('Oro\Bundle\SecurityBundle\Encoder\Mcrypt');
        $encoder->expects($this->once())
            ->method('decryptData')
            ->willReturn($password);

        return $encoder;
    }

    /**
     * @param $host
     * @param $port
     * @param $username
     * @param $encryption
     */
    protected function prepareUserEmailOriginMock($host, $port, $username, $encryption)
    {
        $this->userEmailOrigin->expects($this->once())
            ->method('getSmtpHost')
            ->will($this->returnValue($host));
        $this->userEmailOrigin->expects($this->once())
            ->method('getSmtpPort')
            ->will($this->returnValue($port));
        $this->userEmailOrigin->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($username));
        $this->userEmailOrigin->expects($this->once())
            ->method('getSmtpEncryption')
            ->will($this->returnValue($encryption));
    }

    /**
     * @param $transport
     * @return SendEmailTransport
     */
    protected function prepareEventMock($transport)
    {
        /** @var SendEmailTransport $event */
        $event = $this->getMockBuilder('Oro\Bundle\EmailBundle\Event\SendEmailTransport')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getTransport')
            ->willReturn($transport);
        $event->expects($this->once())
            ->method('getEmailOrigin')
            ->willReturn($this->userEmailOrigin);
        $event->expects($this->once())
            ->method('setTransport');

        return $event;
    }

    public static function transportDataProvider()
    {
        return [
            'imap' => [
                'smtp.gmail.com',
                465,
                'user1',
                'ssl',
                'XOAUTH2',
                'test',
            ],
            'oauth' => [
                'smtp.gmail.com',
                465,
                'user1',
                'ssl',
                null,
                'test',
            ]
        ];
    }
}
