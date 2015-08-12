<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Oro\Bundle\ActivityListBundle\Migration\ActivityListMigration;
use Oro\Bundle\ActivityListBundle\Migration\Extension\ActivityListExtension;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

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

    /**
     * @param ActivityListChainProvider       $provider
     * @param ActivityListExtension           $activityListExtension
     * @param EntityMetadataHelper            $metadataHelper
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     * @param ConfigManager                   $configManager
     */
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
     *
     * @param PostMigrationEvent $event
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
