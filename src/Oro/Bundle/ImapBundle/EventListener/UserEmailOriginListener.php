<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationAlert;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManager;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * This entity listener handles next doctrine entity events:
 * - prePersist: creates ImapEmailFolder entities based on information from UserEmailOrigin;
 * - preUpdate: enables sync of the UserEmailOrigin if refresh token has changed.
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

    /**
     * @deprecated
     *
     * @var OAuthManagerRegistry
     */
    protected $oauthManagerRegistry;

    /**
     * @deprecated
     *
     * @var Registry
     */
    protected $doctrine;

    private NotificationAlertManager $notificationAlertManager;

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
     * @deprecated
     */
    public function setNotificationAlertManager(NotificationAlertManager $notificationAlertManager): void
    {
        $this->notificationAlertManager = $notificationAlertManager;
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

    public function preUpdate(UserEmailOrigin $origin, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('refreshToken')
            && false === $origin->isSyncEnabled()
            && $args->getOldValue('refreshToken') !== $args->getNewValue('refreshToken')
        ) {
            $origin->setIsSyncEnabled(true);
            $em = $args->getEntityManager();
            $em->getUnitOfWork()->recomputeSingleEntityChangeSet(
                $em->getClassMetadata(UserEmailOrigin::class),
                $origin
            );

            $this->notificationAlertManager->resolveNotificationAlertsByAlertTypeForCurrentUser(
                EmailSyncNotificationAlert::ALERT_TYPE_AUTH
            );
            $this->notificationAlertManager->resolveNotificationAlertsByAlertTypeForCurrentUser(
                EmailSyncNotificationAlert::ALERT_TYPE_REFRESH_TOKEN
            );
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
