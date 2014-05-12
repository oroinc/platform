<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendIndicesMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class UpdateExtendIndicesPostUpMigrationListener
{
    /**
     * @var EntityMetadataHelper
     */
    protected $entityMetadataHelper;

    /**
     * @param EntityMetadataHelper $entityMetadataHelper
     */
    public function __construct(EntityMetadataHelper $entityMetadataHelper)
    {
        $this->entityMetadataHelper = $entityMetadataHelper;
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new UpdateExtendIndicesMigration($this->entityMetadataHelper)
        );
    }
}
