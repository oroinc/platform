<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Oro\Bundle\EntityBundle\Migration\UpdateEntityIndexMigration;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class PostUpMigrationListener
{
    /**
     * @var EntityMetadataHelper
     */
    protected $entityMetadataHelper;

    /**
     * @var ConfigProvider
     */
    protected $extendConfigProvider;

    /**
     * @param EntityMetadataHelper $entityMetadataHelper
     * @param ConfigProvider $extendConfigProvider
     */
    public function __construct(
        EntityMetadataHelper $entityMetadataHelper,
        ConfigProvider $extendConfigProvider
    ) {
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->extendConfigProvider = $extendConfigProvider;
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new UpdateEntityIndexMigration(
                $this->entityMetadataHelper,
                $this->extendConfigProvider
            )
        );
    }
}
