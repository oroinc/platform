<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManagerFactory;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerInterface;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use PHPUnit\Framework\TestCase;

class ImapEmailManagerFactoryTest extends TestCase
{
    public function testGetImapEmailManager(): void
    {
        $crypter = $this->createMock(SymmetricCrypterInterface::class);
        $connectorFactory = $this->createMock(ImapConnectorFactory::class);
        $oauthManagerRegistry = $this->createMock(OAuthManagerRegistry::class);
        $oauthManager = $this->createMock(OAuthManagerInterface::class);
        $imapConnector = $this->createMock(ImapConnector::class);
        $origin = $this->createMock(UserEmailOrigin::class);

        $origin->expects($this->exactly(2))
            ->method('getAccountType')
            ->willReturn('someAccountType');

        $oauthManagerRegistry->expects($this->once())
            ->method('hasManager')
            ->with('someAccountType')
            ->willReturn(true);

        $oauthManagerRegistry->expects($this->once())
            ->method('getManager')
            ->with('someAccountType')
            ->willReturn($oauthManager);

        $origin->expects($this->once())
            ->method('getImapHost')
            ->willReturn('imap.example.com');

        $origin->expects($this->once())
            ->method('getImapPort')
            ->willReturn(993);

        $origin->expects($this->once())
            ->method('getImapEncryption')
            ->willReturn('encryption');

        $origin->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $origin->expects($this->once())
            ->method('getUser')
            ->willReturn('user');

        $origin->expects($this->once())
            ->method('getPassword')
            ->willReturn('somePass');

        $oauthManager->expects($this->once())
            ->method('getAccessTokenWithCheckingExpiration')
            ->with($origin)
            ->willReturn('token');

        $crypter->expects($this->once())
            ->method('decryptData')
            ->with('somePass')
            ->willReturnCallback(function ($data) {
                return $data . ' (decrypted)';
            });

        $connectorFactory->expects($this->once())
            ->method('createImapConnector')
            ->willReturn($imapConnector);

        $factory = new ImapEmailManagerFactory($crypter, $connectorFactory, $oauthManagerRegistry);

        $this->assertInstanceOf(ImapEmailManager::class, $factory->getImapEmailManager($origin));
    }
}
