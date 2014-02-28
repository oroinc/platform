<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\CreateMigrationTableMigration;
use Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration;

class UpdateBundleVersionMigrationTest extends \PHPUnit_Framework_TestCase
{
    public function testBundleVersions()
    {
        $schema = new Schema();
        $bundleVersions = [
            'testBundle' => 'v1_0',
            'test1Bundle' => 'v1_1'
        ];

        $updateMigration = new UpdateBundleVersionMigration($bundleVersions);
        $queries = $updateMigration->up($schema);

        $assertQueries = [];
        foreach ($bundleVersions as $bundleName => $version) {
            $assertQueries[] = sprintf(
                "INSERT INTO %s (bundle, version, loaded_at) VALUES ('%s', '%s',",
                CreateMigrationTableMigration::MIGRATION_TABLE,
                $bundleName,
                $version
            );
        }

        foreach ($assertQueries as $index => $query) {
            $this->assertTrue(strpos($queries[$index], $query) === 0);
        }
    }
}
