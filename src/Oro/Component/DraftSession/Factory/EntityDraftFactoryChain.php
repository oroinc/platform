<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Factory;

use Doctrine\Common\Util\ClassUtils;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches draft creation to the appropriate factory based on entity class.
 *
 * Aggregates tagged {@see EntityDraftFactoryInterface} implementations and delegates
 * the createDraft call to the first one that supports the given entity class.
 */
class EntityDraftFactoryChain implements EntityDraftFactoryInterface
{
    /**
     * @param iterable<EntityDraftFactoryInterface> $entityDraftFactories
     */
    public function __construct(
        private readonly iterable $entityDraftFactories,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        foreach ($this->entityDraftFactories as $entityDraftFactory) {
            if ($entityDraftFactory->supports($entityClass)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function createDraft(EntityDraftAwareInterface $entity, string $draftSessionUuid): EntityDraftAwareInterface
    {
        $entityClass = ClassUtils::getClass($entity);

        foreach ($this->entityDraftFactories as $entityDraftFactory) {
            if ($entityDraftFactory->supports($entityClass)) {
                $entityDraft = $entityDraftFactory->createDraft($entity, $draftSessionUuid);

                $event = new EntityDraftCreatedEvent($entity, $entityDraft);
                $this->eventDispatcher->dispatch($event);

                return $entityDraft;
            }
        }

        throw new \LogicException(
            sprintf(
                'No entity draft factory found for entity class "%s".',
                $entityClass,
            )
        );
    }
}
