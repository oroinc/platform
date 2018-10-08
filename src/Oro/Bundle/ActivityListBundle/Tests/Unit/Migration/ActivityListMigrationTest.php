<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Migration;

use Oro\Bundle\ActivityListBundle\Migration\ActivityListMigration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ActivityListMigrationTest extends \PHPUnit\Framework\TestCase
{
    public function testUp()
    {
        $provider = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $activityListExtension = $this
            ->getMockBuilder('Oro\Bundle\ActivityListBundle\Migration\Extension\ActivityListExtension')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataHelper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $nameGenerator = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator')
            ->disableOriginalConstructor()
            ->getMock();
        $schema = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $queries   = new QueryBag();
        $migration = new ActivityListMigration(
            $provider,
            $activityListExtension,
            $metadataHelper,
            $nameGenerator,
            $configManager
        );

        $migration->up($schema, $queries);
        $postQuery = $queries->getPostQueries()[0];

        $this->assertInstanceOf('Oro\Bundle\ActivityListBundle\Migration\ActivityListMigrationQuery', $postQuery);
    }
}
