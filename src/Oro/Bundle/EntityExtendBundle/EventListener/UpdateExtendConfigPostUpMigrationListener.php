<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class UpdateExtendConfigPostUpMigrationListener
{
    /**
     * @var ExtendConfigProcessor
     */
    protected $configProcessor;

    /**
     * @param ExtendConfigProcessor $configProcessor
     */
    public function __construct(ExtendConfigProcessor $configProcessor)
    {
        $this->configProcessor = $configProcessor;
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new UpdateExtendConfigMigration($this->configProcessor)
        );
    }
}
