<?php

namespace Oro\Bundle\DraftBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\DraftBundle\Doctrine\DraftableFilter;

/**
 * Disable/Enable Draftable Filter
 */
class DraftableFilterManager
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param string $className
     */
    public function disable(string $className): void
    {
        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManagerForClass($className);
        $filters = $em->getFilters();
        if ($filters->isEnabled(DraftableFilter::FILTER_ID)) {
            $filters->disable(DraftableFilter::FILTER_ID);
        }
    }

    /**
     * @param string $className
     */
    public function enable(string $className): void
    {
        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManagerForClass($className);
        $filters = $em->getFilters();
        $filters->enable(DraftableFilter::FILTER_ID);
    }
}
