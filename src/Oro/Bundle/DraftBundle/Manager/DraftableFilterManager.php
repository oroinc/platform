<?php

namespace Oro\Bundle\DraftBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DraftBundle\Doctrine\DraftableFilter;

/**
 * Disable/Enable Draftable Filter
 */
class DraftableFilterManager
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    public function disable(string $className): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass($className);
        $filters = $em->getFilters();
        if ($filters->isEnabled(DraftableFilter::FILTER_ID)) {
            $filters->disable(DraftableFilter::FILTER_ID);
        }
    }

    public function enable(string $className): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass($className);
        $filters = $em->getFilters();
        $filters->enable(DraftableFilter::FILTER_ID);
    }
}
