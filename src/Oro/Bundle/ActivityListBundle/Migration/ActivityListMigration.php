<?php

namespace Oro\Bundle\ActivityListBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityListBundle\Migration\Extension\ActivityListExtension;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Manages database schema migrations for activity list functionality.
 *
 * This migration class is responsible for creating and updating the database schema
 * to support activity list tracking. It coordinates with the {@see ActivityListExtension}
 * to establish the necessary tables and associations between the activity list entity
 * and target entities that support activity tracking. The migration is executed during
 * the post-migration phase to ensure all entity configurations are available.
 */
class ActivityListMigration implements Migration
{
    /** @var ActivityListChainProvider */
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

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema instanceof ExtendSchema) {
            $queries->addQuery(
                new ActivityListMigrationQuery(
                    $schema,
                    $this->provider,
                    $this->activityListExtension,
                    $this->metadataHelper,
                    $this->nameGenerator,
                    $this->configManager
                )
            );
        }
    }
}
