<?php

namespace Oro\Bundle\TrackingBundle\Tools;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TrackingBundle\Entity\UniqueTrackingVisit;
use Oro\Bundle\TrackingBundle\Migration\FillUniqueTrackingVisitsQuery;
use Psr\Log\LoggerInterface;

class UniqueTrackingVisitDumper
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FillUniqueTrackingVisitsQuery
     */
    private $fillQuery;

    /**
     * @param ManagerRegistry $registry
     * @param LoggerInterface $logger
     * @param FillUniqueTrackingVisitsQuery $fillQuery
     */
    public function __construct(
        ManagerRegistry $registry,
        LoggerInterface $logger,
        FillUniqueTrackingVisitsQuery $fillQuery
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->fillQuery = $fillQuery;
    }

    /**
     * @return bool
     */
    public function refreshAggregatedData()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(UniqueTrackingVisit::class);
        $em->beginTransaction();
        try {
            /** @var Connection $connection */
            $connection = $this->registry->getConnection();
            $this->fillQuery->setConnection($connection);
            $this->fillQuery->execute($this->logger);
            $em->commit();

            return true;
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                sprintf('Tracking visit aggregation failed: %s', $e->getMessage()),
                ['exception' => $e]
            );
        }

        return false;
    }
}
