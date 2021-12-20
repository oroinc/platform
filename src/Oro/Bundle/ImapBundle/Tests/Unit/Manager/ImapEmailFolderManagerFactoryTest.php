<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManager;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManagerFactory;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class ImapEmailFolderManagerFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetImapEmailFolderManager()
    {
        $crypter = $this->createMock(SymmetricCrypterInterface::class);
        $connectorFactory = $this->createMock(ImapConnectorFactory::class);
        $oauthManagerRegistry = $this->createMock(OAuthManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $imapConnector = $this->createMock(ImapConnector::class);

        $origin = new UserEmailOrigin();

        $connectorFactory->expects(self::once())
            ->method('createImapConnector')
            ->willReturn($imapConnector);

        $factory = new ImapEmailFolderManagerFactory($crypter, $connectorFactory, $oauthManagerRegistry);

        self::assertInstanceOf(ImapEmailFolderManager::class, $factory->getImapEmailFolderManager($origin, $em));
    }
}
