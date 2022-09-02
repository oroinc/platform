<?php

namespace Oro\Bundle\PlatformBundle\MaterializedView;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PlatformBundle\Entity\MaterializedView;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Deletes materialized views taking into account their updatedAt date.
 */
class MaterializedViewRemover implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $managerRegistry;

    private MaterializedViewManager $materializedViewManager;

    public function __construct(ManagerRegistry $managerRegistry, MaterializedViewManager $materializedViewManager)
    {
        $this->managerRegistry = $managerRegistry;
        $this->materializedViewManager = $materializedViewManager;

        $this->logger = new NullLogger();
    }

    /**
     * @param int $daysOld Number of days since the last update to collect materialized views for removal.
     *
     * @return string[] Array of the names of removed materialized views.
     */
    public function removeOlderThan(int $daysOld): array
    {
        $materializedViewEntityRepository = $this->managerRegistry->getRepository(MaterializedView::class);
        $materializedViewNames = $materializedViewEntityRepository
            ->findOlderThan(new \DateTime(sprintf('today -%d days', $daysOld), new \DateTimeZone('UTC')));

        $this->logger->info(
            'Found {count} materialized view older than {daysOld} days for removal.',
            [
                'count' => count($materializedViewNames),
                'daysOld' => $daysOld,
                'materializedViewNames' => $materializedViewNames,
            ]
        );

        foreach ($materializedViewNames as $materializedViewName) {
            $this->materializedViewManager->delete($materializedViewName);
        }

        return $materializedViewNames;
    }
}
