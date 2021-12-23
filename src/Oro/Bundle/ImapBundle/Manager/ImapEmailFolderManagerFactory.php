<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * The factory that creates ImapEmailFolderManager for given email origin.
 */
class ImapEmailFolderManagerFactory
{
    private SymmetricCrypterInterface $crypter;
    private ImapConnectorFactory $connectorFactory;
    private OAuthManagerRegistry $oauthManagerRegistry;

    public function __construct(
        SymmetricCrypterInterface $crypter,
        ImapConnectorFactory $connectorFactory,
        OAuthManagerRegistry $oauthManagerRegistry
    ) {
        $this->crypter = $crypter;
        $this->connectorFactory = $connectorFactory;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
    }

    public function getImapEmailFolderManager(
        UserEmailOrigin $origin,
        EntityManagerInterface $em
    ): ImapEmailFolderManager {
        $manager = $this->oauthManagerRegistry->hasManager($origin->getAccountType())
            ? $this->oauthManagerRegistry->getManager($origin->getAccountType())
            : null;

        $config = new ImapConfig(
            $origin->getImapHost(),
            $origin->getImapPort(),
            $origin->getImapEncryption(),
            $origin->getUser(),
            $this->crypter->decryptData($origin->getPassword()),
            $manager ? $manager->getAccessTokenWithCheckingExpiration($origin) : null
        );

        $connector = $this->connectorFactory->createImapConnector($config);

        return new ImapEmailFolderManager($connector, $em, $origin);
    }
}
