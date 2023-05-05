<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationAlert;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;

/**
 * This entity listener handles next doctrine entity events:
 * - prePersist: creates ImapEmailFolder entities based on information from UserEmailOrigin;
 * - preUpdate: enables sync of the UserEmailOrigin if refresh token has changed.
 */
class UserEmailOriginListener
{
    private NotificationAlertManager $notificationAlertManager;

    public function __construct(NotificationAlertManager $notificationAlertManager)
    {
        $this->notificationAlertManager = $notificationAlertManager;
    }

    /**
     * Create ImapEmailFolder instances for each newly created EmailFolder related to UserEmailOrigin
     */
    public function prePersist(UserEmailOrigin $origin, LifecycleEventArgs $event)
    {
        if (!$origin->getFolders()->isEmpty()) {
            $folders = $origin->getRootFolders();

            $this->createImapEmailFolders($folders, $event->getObjectManager());
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

    protected function createImapEmailFolders(iterable $folders, EntityManager $em): void
    {
        foreach ($folders as $folder) {
            if ($folder->getId() === null) {
                $imapEmailFolder = new ImapEmailFolder();
                $imapEmailFolder->setUidValidity(0);
                $imapEmailFolder->setFolder($folder);

                $em->persist($imapEmailFolder);

                if ($folder->hasSubFolders()) {
                    $this->createImapEmailFolders($folder->getSubFolders(), $em);
                }
            }
        }
    }
}
