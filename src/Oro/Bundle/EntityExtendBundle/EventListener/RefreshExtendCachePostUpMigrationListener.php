<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Migration\RefreshExtendCacheMigration;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class RefreshExtendCachePostUpMigrationListener
{
    /**
     * @var ExtendConfigDumper
     */
    protected $extendConfigDumper;

    /**
     * @var ConfigDumper
     */
    protected $entityConfigDumper;

    /**
     * @var bool
     */
    protected $clearEntityConfigCache;

    /**
     * @param ExtendConfigDumper $extendConfigDumper
     * @param ConfigDumper       $entityConfigDumper
     * @param bool               $clearEntityConfigCache
     */
    public function __construct(
        ExtendConfigDumper $extendConfigDumper,
        ConfigDumper $entityConfigDumper = null,
        $clearEntityConfigCache = false
    ) {
        $this->extendConfigDumper     = $extendConfigDumper;
        $this->entityConfigDumper     = $entityConfigDumper;
        $this->clearEntityConfigCache = $clearEntityConfigCache;
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new RefreshExtendCacheMigration(
                $this->extendConfigDumper,
                $this->entityConfigDumper,
                $this->clearEntityConfigCache
            )
        );
    }
}
