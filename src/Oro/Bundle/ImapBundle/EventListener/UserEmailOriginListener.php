<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManager;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * This entity listener handles prePersist doctrine entity event
 * and creates ImapEmailFolder entities based on information from UserEmailOrigin
 */
class UserEmailOriginListener
{
    /**
     * @deprecated
     *
     * @var SymmetricCrypterInterface
     */
    protected $crypter;

    /**
     * @deprecated
     *
     * @var ImapConnectorFactory
     */
    protected $connectorFactory;

    /** @var Registry */
    protected $doctrine;

    /**
     * @deprecated
     *
     * @var OAuthManagerRegistry
     */
    protected $oauthManagerRegistry;

    public function __construct(
        SymmetricCrypterInterface $crypter,
        ImapConnectorFactory $connectorFactory,
        Registry $doctrine,
        OAuthManagerRegistry $oauthManagerRegistry
    ) {
        $this->crypter = $crypter;
        $this->connectorFactory = $connectorFactory;
        $this->doctrine = $doctrine;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
    }

    /**
     * Create ImapEmailFolder instances for each newly created EmailFolder related to UserEmailOrigin
     */
    public function prePersist(UserEmailOrigin $origin, LifecycleEventArgs $args)
    {
        if (!$origin->getFolders()->isEmpty()) {
            $manager = $this->createManager($origin);
            $folders = $origin->getRootFolders();

            $this->createImapEmailFolders($folders, $manager);
        }
    }

    protected function createImapEmailFolders($folders, ImapEmailFolderManager $manager): void
    {
        foreach ($folders as $folder) {
            if ($folder->getId() === null) {
                $imapEmailFolder = new ImapEmailFolder();
                $imapEmailFolder->setUidValidity(0);
                $imapEmailFolder->setFolder($folder);

                $this->doctrine->getManager()->persist($imapEmailFolder);

                if ($folder->hasSubFolders()) {
                    $this->createImapEmailFolders($folder->getSubFolders(), $manager);
                }
            }
        }
    }

    /**
     * @deprecated
     */
    protected function createManager(UserEmailOrigin $origin): ImapEmailFolderManager
    {
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

        return new ImapEmailFolderManager($connector, $this->doctrine->getManager(), $origin);
    }
}
