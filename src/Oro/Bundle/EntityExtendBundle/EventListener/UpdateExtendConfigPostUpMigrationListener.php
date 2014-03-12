<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigration;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class UpdateExtendConfigPostUpMigrationListener
{
    /**
     * @var ExtendConfigProcessor
     */
    protected $configProcessor;

    /**
     * @var ExtendConfigDumper
     */
    protected $configDumper;

    /**
     * @param ExtendConfigProcessor $configProcessor
     * @param ExtendConfigDumper    $configDumper
     */
    public function __construct(
        ExtendConfigProcessor $configProcessor,
        ExtendConfigDumper $configDumper
    ) {
        $this->configProcessor = $configProcessor;
        $this->configDumper    = $configDumper;
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new UpdateExtendConfigMigration(
                $this->configProcessor,
                $this->configDumper
            )
        );
    }
}
