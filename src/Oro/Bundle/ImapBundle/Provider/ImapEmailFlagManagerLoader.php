<?php

namespace Oro\Bundle\ImapBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Provider\EmailFlagManagerLoaderInterface;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFlagManager;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * This class allows to get EmailFlagManager based on EmailFolder
 */
class ImapEmailFlagManagerLoader implements EmailFlagManagerLoaderInterface
{
    /** @var ImapConnectorFactory */
    protected $connectorFactory;

    /** @var SymmetricCrypterInterface */
    protected $encryptor;

    /** @var OAuthManagerRegistry */
    protected $oauthManagerRegistry;

    public function __construct(
        ImapConnectorFactory $connectorFactory,
        SymmetricCrypterInterface $encryptor,
        OAuthManagerRegistry $oauthManagerRegistry
    ) {
        $this->connectorFactory = $connectorFactory;
        $this->encryptor = $encryptor;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(EmailOrigin $origin)
    {
        return $origin instanceof UserEmailOrigin;
    }

    /**
     * {@inheritdoc}
     */
    public function select(EmailFolder $folder, OroEntityManager $em)
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
            $manager ? $manager->getAccessTokenWithCheckingExpiration($origin) : null
        );

        return new ImapEmailFlagManager(
            $this->connectorFactory->createImapConnector($config),
            $em
        );
    }
}
