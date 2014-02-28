<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\CreateMigrationTableMigration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration;

class UpdateBundleVersionMigrationTest extends \PHPUnit_Framework_TestCase
{
    public function testBundleVersions()
    {
        $schema         = new Schema();
        $queryBag       = new QueryBag();
        $bundleVersions = [
            'testBundle'  => 'v1_0',
            'test1Bundle' => 'v1_1'
        ];

        $updateMigration = new UpdateBundleVersionMigration($bundleVersions);
        $updateMigration->up($schema, $queryBag);

        $assertQueries = [];
        foreach ($bundleVersions as $bundleName => $version) {
            $assertQueries[] = sprintf(
                "INSERT INTO %s (bundle, version, loaded_at) VALUES ('%s', '%s',",
                CreateMigrationTableMigration::MIGRATION_TABLE,
                $bundleName,
                $version
            );
        }

        $this->assertEmpty($queryBag->getPreSqls());
        $postSqls = $queryBag->getPostSqls();
        foreach ($assertQueries as $index => $query) {
            $this->assertTrue(strpos($postSqls[$index], $query) === 0);
        }
    }
}
