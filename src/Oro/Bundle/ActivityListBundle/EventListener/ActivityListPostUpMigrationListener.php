<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Oro\Bundle\ActivityListBundle\Migration\ActivityListMigration;
use Oro\Bundle\ActivityListBundle\Migration\Extension\ActivityListExtension;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

/**
 * Handles post-migration events to set up activity list associations.
 *
 * This listener is triggered after database migrations are completed and creates
 * the necessary activity list associations for all target entities. It instantiates
 * and executes the {@see ActivityListMigration} to ensure that activity list tables and
 * relationships are properly configured for entities that support activity tracking.
 */
class ActivityListPostUpMigrationListener
{
    /**  @var ActivityListChainProvider */
    protected $provider;

    /** @var ActivityListExtension */
    protected $activityListExtension;

    /** @var EntityMetadataHelper */
    protected $metadataHelper;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /** @var ConfigManager */
    protected $configManager;

    public function __construct(
        ActivityListChainProvider $provider,
        ActivityListExtension $activityListExtension,
        EntityMetadataHelper $metadataHelper,
        ExtendDbIdentifierNameGenerator $nameGenerator,
        ConfigManager $configManager
    ) {
        $this->provider              = $provider;
        $this->activityListExtension = $activityListExtension;
        $this->metadataHelper        = $metadataHelper;
        $this->nameGenerator         = $nameGenerator;
        $this->configManager         = $configManager;
    }

    /**
     * POST UP event handler
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new ActivityListMigration(
                $this->provider,
                $this->activityListExtension,
                $this->metadataHelper,
                $this->nameGenerator,
                $this->configManager
            )
        );
    }
}
