<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Event\SendEmailTransport;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\EventListener\SendEmailTransportListener;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerInterface;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class SendEmailTransportListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $crypter;

    /** @var OAuthManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $oauthManagerRegistry;

    /** @var SendEmailTransportListener */
    private $listener;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->oauthManagerRegistry = $this->createMock(OAuthManagerRegistry::class);

        $this->listener = new SendEmailTransportListener($this->crypter, $this->oauthManagerRegistry);
    }

    public function testSendWithSmtpConfigured()
    {
        $decryptedPassword = 'decrypted_pass1';

        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setAccountType('other');
        $userEmailOrigin->setSmtpHost('host1');
        $userEmailOrigin->setSmtpPort(465);
        $userEmailOrigin->setSmtpEncryption('ssl');
        $userEmailOrigin->setUser('user1');
        $userEmailOrigin->setPassword('pass1');

        $this->crypter->expects($this->once())
            ->method('decryptData')
            ->with($userEmailOrigin->getPassword())
            ->willReturn($decryptedPassword);

        /** @var \Swift_Transport_EsmtpTransport|\PHPUnit\Framework\MockObject\MockObject $smtpTransport */
        $smtpTransport = $this->getMockBuilder(\Swift_Transport_EsmtpTransport::class)
            ->disableOriginalConstructor()
            ->setMethods(['setHost', 'setPort', 'setEncryption', 'setUsername', 'setPassword', 'setAuthMode'])
            ->getMock();
        $streamOptions = ['ssl' => ['verify_peer' => false]];
        $smtpTransport->setStreamOptions($streamOptions);
        $smtpTransport->expects($this->once())
            ->method('setHost')
            ->with($userEmailOrigin->getSmtpHost())
            ->willReturnSelf();
        $smtpTransport->expects($this->once())
            ->method('setEncryption')
            ->with($userEmailOrigin->getSmtpEncryption())
            ->willReturnSelf();
        $smtpTransport->expects($this->once())
            ->method('setPort')
            ->with($userEmailOrigin->getSmtpPort())
            ->willReturnSelf();
        $smtpTransport->expects($this->once())
            ->method('setUsername')
            ->with($userEmailOrigin->getUser())
            ->willReturnSelf();

        $this->oauthManagerRegistry->expects($this->once())
            ->method('hasManager')
            ->with($userEmailOrigin->getAccountType())
            ->willReturn(false);
        $this->oauthManagerRegistry->expects($this->never())
            ->method('getManager');

        $smtpTransport->expects($this->once())
            ->method('setPassword')
            ->with($decryptedPassword)
            ->willReturnSelf();

        $event = new SendEmailTransport($userEmailOrigin, $smtpTransport);
        $this->listener->setSmtpTransport($event);

        $this->assertSame($smtpTransport, $event->getTransport());
        $this->assertSame($streamOptions, $smtpTransport->getStreamOptions());
    }

    public function testNewTransportInstanceCreatedInSetSmtpTransport()
    {
        $decryptedPassword = 'decrypted_pass1';

        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setAccountType('oauth1');
        $userEmailOrigin->setSmtpHost('host1');
        $userEmailOrigin->setSmtpPort(465);
        $userEmailOrigin->setSmtpEncryption('ssl');
        $userEmailOrigin->setUser('user1');
        $userEmailOrigin->setPassword('pass1');

        $this->crypter->expects($this->once())
            ->method('decryptData')
            ->with($userEmailOrigin->getPassword())
            ->willReturn($decryptedPassword);

        $smtpTransport = $this->createMock(\Swift_Transport_AbstractSmtpTransport::class);

        $this->oauthManagerRegistry->expects($this->once())
            ->method('hasManager')
            ->with($userEmailOrigin->getAccountType())
            ->willReturn(false);
        $this->oauthManagerRegistry->expects($this->never())
            ->method('getManager');

        $event = new SendEmailTransport($userEmailOrigin, $smtpTransport);
        $this->listener->setSmtpTransport($event);

        /** @var \Swift_SmtpTransport $transport */
        $transport = $event->getTransport();
        $this->assertNotSame($smtpTransport, $transport);
        $this->assertInstanceOf(\Swift_SmtpTransport::class, $transport);
        $this->assertEquals($userEmailOrigin->getSmtpHost(), $transport->getHost());
        $this->assertEquals($userEmailOrigin->getSmtpPort(), $transport->getPort());
        $this->assertEquals($userEmailOrigin->getSmtpEncryption(), $transport->getEncryption());
        $this->assertEquals($userEmailOrigin->getUser(), $transport->getUsername());
        $this->assertEquals($decryptedPassword, $transport->getPassword());
    }

    public function testSendWithOAuthSmtpConfigured()
    {
        $oauthAuthMode = 'XOAUTH1';
        $oauthAccessToken = 'token1';

        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setAccountType('oauth1');
        $userEmailOrigin->setSmtpHost('host1');
        $userEmailOrigin->setSmtpPort(465);
        $userEmailOrigin->setSmtpEncryption('ssl');
        $userEmailOrigin->setUser('user1');
        $userEmailOrigin->setPassword('pass1');

        $this->crypter->expects($this->never())
            ->method('decryptData');

        /** @var \Swift_Transport_EsmtpTransport|\PHPUnit\Framework\MockObject\MockObject $smtpTransport */
        $smtpTransport = $this->getMockBuilder(\Swift_Transport_EsmtpTransport::class)
            ->disableOriginalConstructor()
            ->setMethods(['setHost', 'setPort', 'setEncryption', 'setUsername', 'setPassword', 'setAuthMode'])
            ->getMock();
        $streamOptions = ['ssl' => ['verify_peer' => false]];
        $smtpTransport->setStreamOptions($streamOptions);
        $smtpTransport->expects($this->once())
            ->method('setHost')
            ->with($userEmailOrigin->getSmtpHost())
            ->willReturnSelf();
        $smtpTransport->expects($this->once())
            ->method('setEncryption')
            ->with($userEmailOrigin->getSmtpEncryption())
            ->willReturnSelf();
        $smtpTransport->expects($this->once())
            ->method('setPort')
            ->with($userEmailOrigin->getSmtpPort())
            ->willReturnSelf();
        $smtpTransport->expects($this->once())
            ->method('setUsername')
            ->with($userEmailOrigin->getUser())
            ->willReturnSelf();

        $manager = $this->createMock(OAuthManagerInterface::class);
        $this->oauthManagerRegistry->expects($this->once())
            ->method('hasManager')
            ->with($userEmailOrigin->getAccountType())
            ->willReturn(true);
        $this->oauthManagerRegistry->expects($this->once())
            ->method('getManager')
            ->with($userEmailOrigin->getAccountType())
            ->willReturn($manager);

        $manager->expects($this->once())
            ->method('getAccessTokenWithCheckingExpiration')
            ->willReturn($oauthAccessToken);
        $manager->expects($this->once())
            ->method('getAuthMode')
            ->willReturn($oauthAuthMode);

        $smtpTransport->expects($this->once())
            ->method('setPassword')
            ->with($oauthAccessToken)
            ->willReturnSelf();
        $smtpTransport->expects($this->once())
            ->method('setAuthMode')
            ->with($oauthAuthMode)
            ->willReturnSelf();

        $event = new SendEmailTransport($userEmailOrigin, $smtpTransport);
        $this->listener->setSmtpTransport($event);

        $this->assertSame($smtpTransport, $event->getTransport());
        $this->assertSame($streamOptions, $smtpTransport->getStreamOptions());
    }

    public function testSendWithOAuthSmtpConfiguredButOAuthAccessTokenIsNotSet()
    {
        $decryptedPassword = 'decrypted_pass1';
        $oauthAuthMode = 'XOAUTH1';

        $userEmailOrigin = new UserEmailOrigin();
        $userEmailOrigin->setAccountType('oauth1');
        $userEmailOrigin->setSmtpHost('host1');
        $userEmailOrigin->setSmtpPort(465);
        $userEmailOrigin->setSmtpEncryption('ssl');
        $userEmailOrigin->setUser('user1');
        $userEmailOrigin->setPassword('pass1');

        $this->crypter->expects($this->once())
            ->method('decryptData')
            ->with($userEmailOrigin->getPassword())
            ->willReturn($decryptedPassword);

        /** @var \Swift_Transport_EsmtpTransport|\PHPUnit\Framework\MockObject\MockObject $smtpTransport */
        $smtpTransport = $this->getMockBuilder(\Swift_Transport_EsmtpTransport::class)
            ->disableOriginalConstructor()
            ->setMethods(['setHost', 'setPort', 'setEncryption', 'setUsername', 'setPassword', 'setAuthMode'])
            ->getMock();
        $streamOptions = ['ssl' => ['verify_peer' => false]];
        $smtpTransport->setStreamOptions($streamOptions);
        $smtpTransport->expects($this->once())
            ->method('setHost')
            ->with($userEmailOrigin->getSmtpHost())
            ->willReturnSelf();
        $smtpTransport->expects($this->once())
            ->method('setEncryption')
            ->with($userEmailOrigin->getSmtpEncryption())
            ->willReturnSelf();
        $smtpTransport->expects($this->once())
            ->method('setPort')
            ->with($userEmailOrigin->getSmtpPort())
            ->willReturnSelf();
        $smtpTransport->expects($this->once())
            ->method('setUsername')
            ->with($userEmailOrigin->getUser())
            ->willReturnSelf();

        $manager = $this->createMock(OAuthManagerInterface::class);
        $this->oauthManagerRegistry->expects($this->once())
            ->method('hasManager')
            ->with($userEmailOrigin->getAccountType())
            ->willReturn(true);
        $this->oauthManagerRegistry->expects($this->once())
            ->method('getManager')
            ->with($userEmailOrigin->getAccountType())
            ->willReturn($manager);

        $manager->expects($this->once())
            ->method('getAccessTokenWithCheckingExpiration')
            ->willReturn(null);
        $manager->expects($this->never())
            ->method('getAuthMode');

        $smtpTransport->expects($this->once())
            ->method('setPassword')
            ->with($decryptedPassword)
            ->willReturnSelf();
        $smtpTransport->expects($this->never())
            ->method('setAuthMode');

        $event = new SendEmailTransport($userEmailOrigin, $smtpTransport);
        $this->listener->setSmtpTransport($event);

        $this->assertSame($smtpTransport, $event->getTransport());
        $this->assertSame($streamOptions, $smtpTransport->getStreamOptions());
    }
}
