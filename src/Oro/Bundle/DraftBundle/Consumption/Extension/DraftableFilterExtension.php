<?php

namespace Oro\Bundle\DraftBundle\Consumption\Extension;

use Doctrine\ORM\EntityManagerInterface;
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
        private ManagerRegistry $doctrine,
        private DraftableFilterState $filterState
    ) {
    }

    #[\Override]
    public function onPreReceived(Context $context)
    {
        if ($this->filterState->isDisabled()) {
            return;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManager();
        $filters = $em->getFilters();
        if ($filters->isEnabled(DraftableFilter::FILTER_ID)) {
            $filters->disable(DraftableFilter::FILTER_ID);
            $this->filterState->setDisabled();
        }
    }
}
