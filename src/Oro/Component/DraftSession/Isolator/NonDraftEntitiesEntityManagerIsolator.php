<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Isolator;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Flushes only non-draft entities by temporarily extracting draft entries from the UnitOfWork.
 */
class NonDraftEntitiesEntityManagerIsolator
{
    public function __construct(
        private readonly DraftEntitiesUnitOfWorkIsolator $draftEntitiesUnitOfWorkIsolator,
    ) {
    }

    public function flushNonDraftEntities(EntityManagerInterface $entityManager): void
    {
        $unitOfWork = $entityManager->getUnitOfWork();
        $unitOfWorkSnapshot = $this->draftEntitiesUnitOfWorkIsolator->isolateDraftEntities($unitOfWork);

        try {
            $entityManager->flush();
        } finally {
            $this->draftEntitiesUnitOfWorkIsolator->restoreDraftEntities($unitOfWork, $unitOfWorkSnapshot);
        }
    }
}
