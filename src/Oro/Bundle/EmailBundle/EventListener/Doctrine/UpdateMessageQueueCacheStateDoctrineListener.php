<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\EventListener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;

/**
 * Updates message queue cache state when an {@see EmailTemplateEntity} is created/updated/deleted to keep
 * the runtime TWIG cache of compiled email templates up-to-date.
 */
class UpdateMessageQueueCacheStateDoctrineListener
{
    private CacheState $cacheState;

    private bool $renewCacheState = false;

    public function __construct(CacheState $cacheState)
    {
        $this->cacheState = $cacheState;
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $entityManager = $event->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($this->iterateScheduledEntities($unitOfWork) as $entity) {
            if ($entity instanceof EmailTemplateEntity) {
                $this->renewCacheState = true;
                return;
            }
        }
    }

    private function iterateScheduledEntities(UnitOfWork $unitOfWork): \Generator
    {
        yield from $unitOfWork->getScheduledEntityInsertions();
        yield from $unitOfWork->getScheduledEntityUpdates();
        yield from $unitOfWork->getScheduledEntityDeletions();
    }

    public function postFlush(): void
    {
        if ($this->renewCacheState) {
            $this->cacheState->renewChangeDate();
            $this->renewCacheState = false;
        }
    }
}
