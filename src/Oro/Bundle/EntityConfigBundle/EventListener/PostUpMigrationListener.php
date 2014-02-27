<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class PostUpMigrationListener
{
    /**
     * @var ConfigDumper
     */
    protected $configDumper;

    public function __construct(ConfigDumper $configDumper)
    {
        $this->configDumper = $configDumper;
    }

    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(new UpdateEntityConfigMigration($this->configDumper));
    }
}
