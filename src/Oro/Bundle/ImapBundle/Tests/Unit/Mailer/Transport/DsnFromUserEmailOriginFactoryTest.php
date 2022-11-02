<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mailer\Transport;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Mailer\Transport\DsnFromUserEmailOriginFactory;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerInterface;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Mailer\Transport\Dsn;

class DsnFromUserEmailOriginFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var OAuthManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $oauthManagerRegistry;

    /** @var DsnFromUserEmailOriginFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->oauthManagerRegistry = $this->createMock(OAuthManagerRegistry::class);

        $crypter = $this->createMock(SymmetricCrypterInterface::class);
        $crypter->expects(self::any())
            ->method('decryptData')
            ->willReturnCallback(static fn (string $password) => $password . '-decrypted');

        $this->factory = new DsnFromUserEmailOriginFactory($crypter, $this->oauthManagerRegistry);
    }

    /**
     * @dataProvider createReturnsDsnWithSmtpsWhenEncryptionTlsDataProvider
     */
    public function testCreateReturnsDsnWithSmtpsWhenEncryptionTls(string $smtpEncryption, Dsn $expectedDsn): void
    {
        $userEmailOrigin = (new UserEmailOrigin())
            ->setSmtpEncryption($smtpEncryption)
            ->setSmtpHost('sample-host')
            ->setUser('sample-user')
            ->setPassword('sample-password')
            ->setSmtpPort(42);

        self::assertEquals($expectedDsn, $this->factory->create($userEmailOrigin));
    }

    public function createReturnsDsnWithSmtpsWhenEncryptionTlsDataProvider(): array
    {
        return [
            [
                'tls',
                new Dsn('smtp', 'sample-host', 'sample-user', 'sample-password-decrypted', 42),
            ],
            [
                'ssl',
                new Dsn('smtps', 'sample-host', 'sample-user', 'sample-password-decrypted', 42),
            ],
            [
                '',
                new Dsn('smtp', 'sample-host', 'sample-user', 'sample-password-decrypted', 42),
            ],
        ];
    }

    public function testCreateReturnsDsnWithAccessTokenWhenNotExpired(): void
    {
        $userEmailOrigin = (new UserEmailOrigin())
            ->setAccountType('sample-type')
            ->setSmtpEncryption('tls')
            ->setSmtpHost('sample-host')
            ->setUser('sample-user')
            ->setPassword('sample-password')
            ->setSmtpPort(42);

        $this->oauthManagerRegistry->expects(self::once())
            ->method('hasManager')
            ->with('sample-type')
            ->willReturn(true);

        $oauthManager = $this->createMock(OAuthManagerInterface::class);
        $this->oauthManagerRegistry->expects(self::once())
            ->method('getManager')
            ->with('sample-type')
            ->willReturn($oauthManager);

        $oauthManager->expects(self::once())
            ->method('getAccessTokenWithCheckingExpiration')
            ->with($userEmailOrigin)
            ->willReturn('access-token');

        $expected = new Dsn('smtp', 'sample-host', 'sample-user', 'access-token', 42);

        self::assertEquals($expected, $this->factory->create($userEmailOrigin));
    }

    public function testCreateReturnsDsnWithPasswordWhenAccessTokenExpired(): void
    {
        $userEmailOrigin = (new UserEmailOrigin())
            ->setAccountType('sample-type')
            ->setSmtpEncryption('tls')
            ->setSmtpHost('sample-host')
            ->setUser('sample-user')
            ->setPassword('sample-password')
            ->setSmtpPort(42);

        $this->oauthManagerRegistry->expects(self::once())
            ->method('hasManager')
            ->with('sample-type')
            ->willReturn(true);

        $oauthManager = $this->createMock(OAuthManagerInterface::class);
        $this->oauthManagerRegistry->expects(self::once())
            ->method('getManager')
            ->with('sample-type')
            ->willReturn($oauthManager);

        $oauthManager->expects(self::once())
            ->method('getAccessTokenWithCheckingExpiration')
            ->with($userEmailOrigin)
            ->willReturn(null);

        $expected = new Dsn('smtp', 'sample-host', 'sample-user', 'sample-password-decrypted', 42);

        self::assertEquals($expected, $this->factory->create($userEmailOrigin));
    }
}
