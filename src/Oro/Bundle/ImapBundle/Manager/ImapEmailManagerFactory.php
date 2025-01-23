<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Laminas\Mail\Exception\RuntimeException;
use Oro\Bundle\EmailBundle\Exception\SyncWithNotificationAlertException;
use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationAlert;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * The factory that creates ImapEmailManager for given email origin.
 */
class ImapEmailManagerFactory
{
    public function __construct(
        private SymmetricCrypterInterface $crypter,
        private ImapConnectorFactory $connectorFactory,
        private OAuthManagerRegistry $oauthManagerRegistry
    ) {
    }

    public function getImapEmailManager(
        UserEmailOrigin $origin,
    ): ImapEmailManager {
        $manager = $this->oauthManagerRegistry->hasManager($origin->getAccountType())
            ? $this->oauthManagerRegistry->getManager($origin->getAccountType())
            : null;

        $config = new ImapConfig(
            $origin->getImapHost(),
            $origin->getImapPort(),
            $origin->getImapEncryption(),
            $origin->getUser(),
            $this->crypter->decryptData($origin->getPassword()),
            $manager?->getAccessTokenWithCheckingExpiration($origin)
        );

        $connector = $this->connectorFactory->createImapConnector($config);

        try {
            return new ImapEmailManager($connector);
        } catch (RuntimeException $e) {
            throw new SyncWithNotificationAlertException(
                EmailSyncNotificationAlert::createForSwitchFolderFail(
                    'Cannot connect to the IMAP server. Exception message:' . $e->getMessage()
                ),
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
