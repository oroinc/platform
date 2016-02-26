<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManager;
use Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOauth2Manager;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class UserEmailOriginListener
{
    /**
     * @var Mcrypt
     */
    protected $mcrypt;

    /**
     * @var ImapConnectorFactory
     */
    protected $connectorFactory;

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var ImapEmailGoogleOauth2Manager
     */
    protected $imapEmailGoogleOauth2Manager;

    /**
     * @param Mcrypt $mcrypt
     * @param ImapConnectorFactory $connectorFactory
     * @param Registry $doctrine
     * @param ImapEmailGoogleOauth2Manager $imapEmailGoogleOauth2Manager
     */
    public function __construct(
        Mcrypt $mcrypt,
        ImapConnectorFactory $connectorFactory,
        Registry $doctrine,
        ImapEmailGoogleOauth2Manager $imapEmailGoogleOauth2Manager
    ) {
        $this->mcrypt = $mcrypt;
        $this->connectorFactory = $connectorFactory;
        $this->doctrine = $doctrine;
        $this->imapEmailGoogleOauth2Manager = $imapEmailGoogleOauth2Manager;
    }

    /**
     * Create ImapEmailFolder instances for each newly created EmailFolder related to UserEmailOrigin
     *
     * @param LifecycleEventArgs $event
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $origin = $event->getObject();
        if ($origin instanceof UserEmailOrigin && !$origin->getFolders()->isEmpty()) {
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
        $config = new ImapConfig(
            $origin->getImapHost(),
            $origin->getImapPort(),
            $origin->getImapEncryption(),
            $origin->getUser(),
            $this->mcrypt->decryptData($origin->getPassword()),
            $this->imapEmailGoogleOauth2Manager->getAccessTokenWithCheckingExpiration($origin)
        );

        $connector = $this->connectorFactory->createImapConnector($config);

        return new ImapEmailFolderManager($connector, $this->doctrine->getManager(), $origin);
    }
}
