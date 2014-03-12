<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Migration\RefreshExtendCacheMigration;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class RefreshExtendCachePostUpMigrationListener
{
    /**
     * @var ExtendConfigDumper
     */
    protected $configDumper;

    /**
     * @var mixed
     */
    protected $installed;

    /**
     * @param ExtendConfigDumper $configDumper
     * @param mixed              $installed
     */
    public function __construct(ExtendConfigDumper $configDumper, $installed)
    {
        $this->configDumper = $configDumper;
        $this->installed    = $installed;
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        if ($this->installed) {
            $event->addMigration(
                new RefreshExtendCacheMigration($this->configDumper)
            );
        }
    }
}
