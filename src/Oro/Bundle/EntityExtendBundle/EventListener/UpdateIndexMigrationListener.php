<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Doctrine\DBAL\Driver\Connection;

use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendIndexMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class UpdateIndexMigrationListener
{
    /**
     * @var EntityMetadataHelper
     */
    protected $entityMetadataHelper;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param EntityMetadataHelper $entityMetadataHelper
     * @param \Symfony\Bridge\Doctrine\ManagerRegistry $doctrine
     */
    public function __construct(
        EntityMetadataHelper $entityMetadataHelper,
        ManagerRegistry $doctrine
    ) {
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->connection           = $doctrine->getConnection();
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new UpdateExtendIndexMigration(
                $this->entityMetadataHelper,
                $this->connection
            )
        );
    }
}
