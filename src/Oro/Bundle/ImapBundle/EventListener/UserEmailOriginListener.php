<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
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
     * @var SymmetricCrypterInterface
     */
    protected $crypter;

    /**
     * @var ImapConnectorFactory
     */
    protected $connectorFactory;

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
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

    /**
     * @param ArrayCollection|EmailFolder[] $folders
     * @param ImapEmailFolderManager $manager
     */
    protected function createImapEmailFolders($folders, ImapEmailFolderManager $manager)
    {
        foreach ($folders as $folder) {
            if ($folder->getId() === null) {
                $uidValidity = $manager->getUidValidity($folder);

                if ($uidValidity !== null) {
                    $imapEmailFolder = new ImapEmailFolder();
                    $imapEmailFolder->setUidValidity($uidValidity);
                    $imapEmailFolder->setFolder($folder);

                    $this->doctrine->getManager()->persist($imapEmailFolder);
                }

                if ($folder->hasSubFolders()) {
                    $this->createImapEmailFolders($folder->getSubFolders(), $manager);
                }
            }
        }
    }

    /**
     * @param UserEmailOrigin $origin
     *
     * @return ImapEmailFolderManager
     */
    protected function createManager(UserEmailOrigin $origin)
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
