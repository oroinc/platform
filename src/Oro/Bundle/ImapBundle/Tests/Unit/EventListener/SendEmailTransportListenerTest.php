<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\EventListener;

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
        $transport = new \Swift_SmtpTransport();
        $encoder = $this->getEncoderMock($password);

        if ($authMode) {
            $this->imapEmailGoogleOauth2Manager
                ->expects($this->once())
                ->method('getAccessTokenWithCheckingExpiration')
                ->willReturn('test');
        }
        $sendEmailTransportListener = new SendEmailTransportListener($encoder, $this->imapEmailGoogleOauth2Manager);

        $this->prepareUserEmailOriginMock($host, $port, $username, $encryption);
        $event = $this->prepareEventMock($transport);

        $sendEmailTransportListener->setSmtpTransport($event);

        $this->assertEquals($host, $transport->getHost());
        $this->assertEquals($port, $transport->getPort());
        $this->assertEquals($username, $transport->getUsername());
        $this->assertEquals($password, $transport->getPassword());
        $this->assertEquals($authMode, $transport->getAuthMode());
        $this->assertEquals($encryption, $transport->getEncryption());
    }

    /**
     * @param $password
     * @return \Oro\Bundle\SecurityBundle\Encoder\Mcrypt|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEncoderMock($password)
    {
        $encoder = $this->getMock('Oro\Bundle\SecurityBundle\Encoder\Mcrypt');
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
