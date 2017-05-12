<?php

namespace Oro\Bundle\TrackingBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TrackingBundle\Entity\Repository\UniqueTrackingVisitRepository;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\UniqueTrackingVisit;

class TrackingVisitEntityListener
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param ConfigManager $configManager
     * @param ManagerRegistry $registry
     */
    public function __construct(ConfigManager $configManager, ManagerRegistry $registry)
    {
        $this->configManager = $configManager;
        $this->registry = $registry;
    }

    /**
     * @param TrackingVisit $trackingVisit
     */
    public function prePersist(TrackingVisit $trackingVisit)
    {
        if (!$this->configManager->get('oro_tracking.precalculated_statistic_enabled')) {
            return;
        }
        /** @var UniqueTrackingVisitRepository $repository */
        $repository = $this->registry->getManagerForClass(UniqueTrackingVisit::class)
            ->getRepository(UniqueTrackingVisit::class);

        $timezoneName = $this->configManager->get('oro_locale.timezone');
        if (!$timezoneName) {
            $timezoneName = 'UTC';
        }
        $timezone = new \DateTimeZone($timezoneName);

        $repository->logTrackingVisit($trackingVisit, $timezone);
    }
}
