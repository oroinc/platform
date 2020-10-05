<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Event\SendEmailTransport;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\EventListener\SendEmailTransportListener;
use Oro\Bundle\ImapBundle\Manager\Oauth2ManagerInterface;
use Oro\Bundle\ImapBundle\Manager\OAuth2ManagerRegistry;
use Oro\Bundle\ImapBundle\Tests\Unit\TestCase\OauthManagerRegistryAwareTestCase;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use PHPUnit\Framework\MockObject\MockObject;

class SendEmailTransportListenerTest extends OauthManagerRegistryAwareTestCase
{
    /** @var MockObject|OAuth2ManagerRegistry */
    protected $oauthManagerRegistry;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->oauthManagerRegistry = $this->getManagerRegistryMock();
    }

    /**
     * @dataProvider transportDataProvider
     */
    public function testSendWithSmtpConfigured($host, $port, $username, $encryption, $authMode, $password)
    {
        /** @var \Swift_Transport_EsmtpTransport|MockObject $smtpTransportMock */
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
        /** @var Oauth2ManagerInterface|MockObject $manager */
        $manager = $this->oauthManagerRegistry->getManager(self::MANAGER_TYPE_DEFAULT);
        $userOriginMock = $this->prepareUserEmailOriginMock($host, $port, $username, $encryption);
        if ($authMode) {
            $manager
                ->expects($this->once())
                ->method('getAccessTokenWithCheckingExpiration')
                ->willReturn('test');
            $smtpTransportMock->expects($this->once())
                ->method('setAuthMode')
                ->with($authMode)
                ->willReturnSelf();
        } else {
            $userOriginMock->expects($this->once())
                ->method('getPassword')
                ->willReturn($password);
            $smtpTransportMock->expects($this->once())
                ->method('setPassword')
                ->with($password)
                ->willReturnSelf();
        }
        $sendEmailTransportListener = new SendEmailTransportListener($encoder, $this->oauthManagerRegistry);

        $event = $this->prepareEventMock($smtpTransportMock, $userOriginMock);

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

        /** @var \Swift_Transport_AbstractSmtpTransport|MockObject $smtpTransportMock */
        $smtpTransportMock = $this->getMockBuilder(\Swift_Transport_AbstractSmtpTransport::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $encoder = $this->getEncoderMock($password);
        $sendEmailTransportListener = new SendEmailTransportListener($encoder, $this->oauthManagerRegistry);

        $userEmailOrigin = $this->prepareUserEmailOriginMock($host, $port, $username, $encryption);

        $event = new SendEmailTransport($userEmailOrigin, $smtpTransportMock);
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
     * @return SymmetricCrypterInterface|MockObject
     */
    protected function getEncoderMock($password)
    {
        $encoder = $this->createMock(SymmetricCrypterInterface::class);
        $encoder->expects($this->once())
            ->method('decryptData')
            ->willReturn($password);

        return $encoder;
    }

    /**
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $encryption
     *
     * @return MockObject|UserEmailOrigin
     */
    protected function prepareUserEmailOriginMock($host, $port, $username, $encryption)
    {
        $emailOrigin = $this->getEmailOriginMock(self::MANAGER_TYPE_DEFAULT);
        $emailOrigin->expects($this->once())
            ->method('getSmtpHost')
            ->will($this->returnValue($host));
        $emailOrigin->expects($this->once())
            ->method('getSmtpPort')
            ->will($this->returnValue($port));
        $emailOrigin->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($username));
        $emailOrigin->expects($this->once())
            ->method('getSmtpEncryption')
            ->will($this->returnValue($encryption));

        return $emailOrigin;
    }

    /**
     * @param MockObject|\Swift_Transport_EsmtpTransport $transport
     * @param MockObject|UserEmailOrigin
     * @return SendEmailTransport
     */
    protected function prepareEventMock($transport, $userOriginMock)
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
            ->willReturn($userOriginMock);
        $event->expects($this->once())
            ->method('setTransport');

        return $event;
    }

    public static function transportDataProvider(): array
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
