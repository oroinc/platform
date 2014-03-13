<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Oro\Bundle\EntityBundle\Migration\UpdateEntityIndexMigration;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class PostUpMigrationListener
{
    /**
     * @var EntityClassResolver
     */
    protected $entityClassResolver;

    /**
     * @var ConfigProvider
     */
    protected $extendConfigProvider;

    /**
     * @param EntityClassResolver $entityClassResolver
     * @param ConfigProvider $extendConfigProvider
     */
    public function __construct(EntityClassResolver $entityClassResolver, ConfigProvider $extendConfigProvider)
    {
        $this->entityClassResolver  = $entityClassResolver;
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
                $this->entityClassResolver,
                $this->extendConfigProvider
            )
        );
    }
}
