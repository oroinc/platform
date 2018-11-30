<?php

namespace Oro\Bundle\NotificationBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Entity\Event;
use Oro\Bundle\NotificationBundle\Provider\NotificationManager;

/**
 * Clears the notification rules cache if there are changes related to
 * EmailNotification or Event entities that affect this cache.
 */
class InvalidateCacheListener
{
    /** @var NotificationManager */
    private $notificationManager;

    /** @var bool */
    private $needToRemoveRulesCache = false;

    /**
     * @param NotificationManager $notificationManager
     */
    public function __construct(NotificationManager $notificationManager)
    {
        $this->notificationManager = $notificationManager;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->needToRemoveRulesCache && $this->isRulesCacheDirty($args->getEntityManager()->getUnitOfWork())) {
            $this->needToRemoveRulesCache = true;
        }
    }

    public function postFlush()
    {
        if ($this->needToRemoveRulesCache) {
            $this->notificationManager->clearCache();
            $this->needToRemoveRulesCache = false;
        }
    }

    /**
     * @param UnitOfWork $uow
     *
     * @return bool
     */
    private function isRulesCacheDirty(UnitOfWork $uow): bool
    {
        if ($this->hasEmailNotificationOrEventEntity($uow->getScheduledEntityInsertions())) {
            return true;
        }

        if ($this->hasEmailNotificationOrEventEntity($uow->getScheduledEntityDeletions())) {
            return true;
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof EmailNotification) {
                $changeSet = $uow->getEntityChangeSet($entity);
                if (isset($changeSet['entityName']) || isset($changeSet['event'])) {
                    return true;
                }
            } elseif ($entity instanceof Event) {
                $changeSet = $uow->getEntityChangeSet($entity);
                if (isset($changeSet['name'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array $entities
     *
     * @return bool
     */
    private function hasEmailNotificationOrEventEntity(array $entities): bool
    {
        foreach ($entities as $entity) {
            if ($entity instanceof EmailNotification || $entity instanceof Event) {
                return true;
            }
        }

        return false;
    }
}
