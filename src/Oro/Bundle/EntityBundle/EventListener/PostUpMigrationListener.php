<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class PostUpMigrationListener
{
    /**
     * @var ConfigDumper
     */
    protected $configDumper;

    /**
     * @param ConfigDumper $configDumper
     */
    public function __construct(ConfigDumper $configDumper)
    {
        $this->configDumper = $configDumper;
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        var_dump('EntityBundle postUp migration');
        /*$event->addMigration(
            new UpdateEntityConfigMigration($this->configDumper)
        );*/
    }
}
