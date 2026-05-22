<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Synchronizer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Event\EntityFromDraftSyncBeforeEvent;
use Oro\Component\DraftSession\Event\EntityFromDraftSyncEvent;
use Oro\Component\DraftSession\Event\EntityToDraftSyncBeforeEvent;
use Oro\Component\DraftSession\Event\EntityToDraftSyncEvent;
use Oro\Component\DraftSession\Exception\DraftSessionLogicException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches draft synchronization to all entity synchronizers that support the given entity class,
 * and dispatches direction-specific events before and after synchronization.
 *
 * For synchronizeFromDraft: dispatches {@see EntityFromDraftSyncBeforeEvent} before and
 * {@see EntityFromDraftSyncEvent} after synchronizers run.
 * For synchronizeToDraft: dispatches {@see EntityToDraftSyncBeforeEvent} before and
 * {@see EntityToDraftSyncEvent} after synchronizers run.
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

        $supportingSynchronizers = $this->collectSupportingSynchronizers($entityClass);

        if (empty($supportingSynchronizers)) {
            throw new DraftSessionLogicException(
                sprintf('No entity draft synchronizer found for entity class "%s".', $entityClass)
            );
        }

        $this->eventDispatcher->dispatch(new EntityFromDraftSyncBeforeEvent($draft, $entity));

        foreach ($supportingSynchronizers as $entityDraftSynchronizer) {
            $entityDraftSynchronizer->synchronizeFromDraft($draft, $entity);
        }

        $this->eventDispatcher->dispatch(new EntityFromDraftSyncEvent($draft, $entity));
    }

    #[\Override]
    public function synchronizeToDraft(
        EntityDraftAwareInterface $entity,
        EntityDraftAwareInterface $draft,
    ): void {
        $entityClass = ClassUtils::getClass($entity);

        $supportingSynchronizers = $this->collectSupportingSynchronizers($entityClass);

        if (empty($supportingSynchronizers)) {
            throw new DraftSessionLogicException(
                sprintf('No entity draft synchronizer found for entity class "%s".', $entityClass)
            );
        }

        $this->eventDispatcher->dispatch(new EntityToDraftSyncBeforeEvent($entity, $draft));

        foreach ($supportingSynchronizers as $entityDraftSynchronizer) {
            $entityDraftSynchronizer->synchronizeToDraft($entity, $draft);
        }

        $this->eventDispatcher->dispatch(new EntityToDraftSyncEvent($entity, $draft));
    }

    /**
     * Collects all synchronizers that support the given entity class in a single iteration.
     *
     * @return list<EntityDraftSynchronizerInterface>
     */
    private function collectSupportingSynchronizers(string $entityClass): array
    {
        $supporting = [];
        foreach ($this->entityDraftSynchronizers as $entityDraftSynchronizer) {
            if ($entityDraftSynchronizer->supports($entityClass)) {
                $supporting[] = $entityDraftSynchronizer;
            }
        }

        return $supporting;
    }
}
