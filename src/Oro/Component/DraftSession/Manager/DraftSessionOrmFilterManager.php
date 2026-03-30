<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Manager;

use Doctrine\Persistence\ManagerRegistry;

/**
 * Manages the draft session ORM filter state.
 */
class DraftSessionOrmFilterManager
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly string $entityClass,
        private readonly string $ormFilterName,
    ) {
    }

    public function disable(): void
    {
        $entityManager = $this->doctrine->getManagerForClass($this->entityClass);

        $filters = $entityManager->getFilters();
        if ($filters->isEnabled($this->ormFilterName)) {
            $filters->disable($this->ormFilterName);
        }
    }

    public function enable(): void
    {
        $entityManager = $this->doctrine->getManagerForClass($this->entityClass);

        $filters = $entityManager->getFilters();
        if (!$filters->isEnabled($this->ormFilterName)) {
            $filters->enable($this->ormFilterName);
        }
    }

    public function isEnabled(): bool
    {
        $entityManager = $this->doctrine->getManagerForClass($this->entityClass);

        return $entityManager->getFilters()->isEnabled($this->ormFilterName);
    }
}
