<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Synchronizer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Event\EntityFromDraftSyncEvent;
use Oro\Component\DraftSession\Event\EntityToDraftSyncEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches draft synchronization to all entity synchronizers that support the given entity class,
 * and dispatches {@see EntityFromDraftSyncEvent} / {@see EntityToDraftSyncEvent} afterwards.
 *
 * Aggregates tagged {@see EntityDraftSynchronizerInterface} implementations and delegates
 * the synchronize call to every one that supports the given entity class.
 */
class EntityDraftSynchronizerChain implements EntityDraftSynchronizerInterface
{
    /**
     * @param iterable<EntityDraftSynchronizerInterface> $entityDraftSynchronizers
     */
    public function __construct(
        private readonly iterable $entityDraftSynchronizers,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        foreach ($this->entityDraftSynchronizers as $entityDraftSynchronizer) {
            if ($entityDraftSynchronizer->supports($entityClass)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function synchronizeFromDraft(
        EntityDraftAwareInterface $draft,
        EntityDraftAwareInterface $entity,
    ): void {
        $entityClass = ClassUtils::getClass($draft);

        $found = false;
        foreach ($this->entityDraftSynchronizers as $entityDraftSynchronizer) {
            if ($entityDraftSynchronizer->supports($entityClass)) {
                $entityDraftSynchronizer->synchronizeFromDraft($draft, $entity);
                $found = true;
            }
        }

        if (!$found) {
            throw new \LogicException(
                sprintf(
                    'No entity draft synchronizer found for entity class "%s".',
                    $entityClass,
                )
            );
        }

        $this->eventDispatcher->dispatch(new EntityFromDraftSyncEvent($draft, $entity));
    }

    #[\Override]
    public function synchronizeToDraft(
        EntityDraftAwareInterface $entity,
        EntityDraftAwareInterface $draft,
    ): void {
        $entityClass = ClassUtils::getClass($entity);

        $found = false;
        foreach ($this->entityDraftSynchronizers as $entityDraftSynchronizer) {
            if ($entityDraftSynchronizer->supports($entityClass)) {
                $entityDraftSynchronizer->synchronizeToDraft($entity, $draft);
                $found = true;
            }
        }

        if (!$found) {
            throw new \LogicException(
                sprintf(
                    'No entity draft synchronizer found for entity class "%s".',
                    $entityClass,
                )
            );
        }

        $this->eventDispatcher->dispatch(new EntityToDraftSyncEvent($entity, $draft));
    }
}
