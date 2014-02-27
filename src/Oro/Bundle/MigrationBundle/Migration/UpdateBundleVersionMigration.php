<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

class UpdateBundleVersionMigration implements Migration
{
    /**
     * @var array
     */
    protected $bundleVersions;

    /**
     * @param array $bundleVersions
     */
    public function __construct(array $bundleVersions)
    {
        $this->bundleVersions = $bundleVersions;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        $versionsSql = [];
        if (!empty($this->bundleVersions)) {
            $date = new \DateTime();
            foreach ($this->bundleVersions as $bundleName => $bundleVersion) {
                $versionsSql[] = sprintf(
                    "INSERT INTO %s (bundle, version, loaded_at) VALUES ('%s', '%s', '%s')",
                    CreateMigrationTableMigration::MIGRATION_TABLE,
                    $bundleName,
                    $bundleVersion,
                    $date->format('Y-m-d H:i:s')
                );
            }
        }

        return $versionsSql;
    }
}
