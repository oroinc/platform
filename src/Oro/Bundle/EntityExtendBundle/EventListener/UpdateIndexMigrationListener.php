<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
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
            new UpdateExtendIndexMigration(
                $this->entityMetadataHelper,
                $this->extendConfigProvider
            )
        );
    }
}
