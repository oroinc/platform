<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Migration;

use Oro\Bundle\ActivityListBundle\Migration\ActivityListMigration;
use Oro\Bundle\ActivityListBundle\Migration\ActivityListMigrationQuery;
use Oro\Bundle\ActivityListBundle\Migration\Extension\ActivityListExtension;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ActivityListMigrationTest extends \PHPUnit\Framework\TestCase
{
    public function testUp()
    {
        $provider = $this->createMock(ActivityListChainProvider::class);
        $activityListExtension = $this->createMock(ActivityListExtension::class);
        $metadataHelper = $this->createMock(EntityMetadataHelper::class);
        $nameGenerator = $this->createMock(ExtendDbIdentifierNameGenerator::class);
        $schema = $this->createMock(ExtendSchema::class);
        $configManager = $this->createMock(ConfigManager::class);

        $queries = new QueryBag();
        $migration = new ActivityListMigration(
            $provider,
            $activityListExtension,
            $metadataHelper,
            $nameGenerator,
            $configManager
        );

        $migration->up($schema, $queries);
        $postQuery = $queries->getPostQueries()[0];

        $this->assertInstanceOf(ActivityListMigrationQuery::class, $postQuery);
    }
}
