<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Isolator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Oro\Component\DraftSession\Entity\DraftSessionAwareInterface;
use Oro\Component\DraftSession\Util\EntityDraftUtils;

/**
 * Flushes only draft session entities by temporarily extracting non-draft scheduling entries from the UnitOfWork.
 */
class DraftEntitiesEntityManagerIsolator
{
    public function __construct(
        private readonly NonDraftEntitiesUnitOfWorkIsolator $nonDraftEntitiesUnitOfWorkIsolator,
    ) {
    }

    public function flushDraftEntities(EntityManagerInterface $entityManager): void
    {
        $unitOfWork = $entityManager->getUnitOfWork();
        $scheduledForFlush = $this->collectDraftEntities($unitOfWork);
        $unitOfWorkSnapshot = $this->nonDraftEntitiesUnitOfWorkIsolator->isolateNonDraftEntities($unitOfWork);

        try {
            $entityManager->flush($scheduledForFlush ?: null);
        } finally {
            $this->nonDraftEntitiesUnitOfWorkIsolator->restoreNonDraftEntities($unitOfWork, $unitOfWorkSnapshot);
        }
    }

    /**
     * @param UnitOfWork $unitOfWork
     *
     * @return list<DraftSessionAwareInterface>
     */
    private function collectDraftEntities(UnitOfWork $unitOfWork): array
    {
        /** @var list<DraftSessionAwareInterface> $scheduledForFlush */
        $scheduledForFlush = [];

        foreach ($this->getDraftSessionAwareEntities($unitOfWork) as $entity) {
            if (EntityDraftUtils::isEntityDraft($entity)) {
                $scheduledForFlush[] = $entity;
            }
        }

        return $scheduledForFlush;
    }

    private function getDraftSessionAwareEntities(UnitOfWork $unitOfWork): \Generator
    {
        foreach ($unitOfWork->getIdentityMap() as $entityClass => $entities) {
            if (!is_a($entityClass, DraftSessionAwareInterface::class, true)) {
                continue;
            }

            yield from $entities;
        }

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof DraftSessionAwareInterface) {
                continue;
            }

            yield $entity;
        }
    }
}
