<?php

namespace Oro\Bundle\ImapBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerInterface;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerLoaderInterface;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFlagManager;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * Allows to get an IMAP email flag manager.
 */
class ImapEmailFlagManagerLoader implements EmailFlagManagerLoaderInterface
{
    public function __construct(
        private ImapConnectorFactory $connectorFactory,
        private SymmetricCrypterInterface $encryptor,
        private OAuthManagerRegistry $oauthManagerRegistry
    ) {
    }

    #[\Override]
    public function supports(EmailOrigin $origin): bool
    {
        return $origin instanceof UserEmailOrigin;
    }

    #[\Override]
    public function select(EmailFolder $folder, EntityManagerInterface $em): EmailFlagManagerInterface
    {
        /** @var UserEmailOrigin $origin */
        $origin = $folder->getOrigin();
        $manager = $this->oauthManagerRegistry->hasManager($origin->getAccountType())
            ? $this->oauthManagerRegistry->getManager($origin->getAccountType())
            : null;

        $config = new ImapConfig(
            $origin->getImapHost(),
            $origin->getImapPort(),
            $origin->getImapEncryption(),
            $origin->getUser(),
            $this->encryptor->decryptData($origin->getPassword()),
            $manager?->getAccessTokenWithCheckingExpiration($origin)
        );

        return new ImapEmailFlagManager(
            $this->connectorFactory->createImapConnector($config),
            $em
        );
    }
}
