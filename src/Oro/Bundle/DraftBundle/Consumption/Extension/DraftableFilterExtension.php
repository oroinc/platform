<?php

namespace Oro\Bundle\DraftBundle\Consumption\Extension;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DraftBundle\Doctrine\DraftableFilter;
use Oro\Bundle\DraftBundle\Manager\DraftableFilterState;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Disable Draftable Filter
 */
class DraftableFilterExtension extends AbstractExtension
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        protected DraftableFilterState $filterState
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        if ($this->filterState->isDisabled()) {
            return;
        }
        $this->disableDraftableFilter();
    }

    private function disableDraftableFilter(): void
    {
        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManager();
        $filters = $em->getFilters();
        if ($filters->isEnabled(DraftableFilter::FILTER_ID)) {
            $filters->disable(DraftableFilter::FILTER_ID);
            $this->filterState->setDisabled();
        }
    }
}
