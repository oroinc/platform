<?php

namespace Oro\Bundle\DraftBundle\Consumption\Extension;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DraftBundle\Doctrine\DraftableFilter;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Disable Draftable Filter
 */
class DraftableFilterExtension extends AbstractExtension
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        $this->disableDraftableFilter();
    }

    private function disableDraftableFilter(): void
    {
        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManager();
        $filters = $em->getFilters();
        if ($filters->isEnabled(DraftableFilter::FILTER_ID)) {
            $filters->disable(DraftableFilter::FILTER_ID);
        }
    }
}
