<?php
namespace Oro\Bundle\InstallerBundle\Migrations\MigrationTable;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\MigrationsLoader;

class UpdateBundleVersions
{
    protected $bundleVersions;

    public function setBundleVersions($bundleVersions)
    {
        $this->bundleVersions = $bundleVersions;
    }

    /**
     * @param Schema $schema
     * @return array
     */
    public function up(Schema $schema)
    {
        $versionsSql = [];
        if (!empty($this->bundleVersions)) {
            $date = new \DateTime();
            foreach ($this->bundleVersions as $bundleName => $bundleVersion) {
                $versionsSql[] = sprintf(
                    "INSERT INTO %s SET bundle = '%s', version = '%s', date = '%s'",
                    MigrationsLoader::MIGRATION_TABLE,
                    $bundleName,
                    $bundleVersion,
                    $date->format('Y-m-d H:i:s')
                );
            }
        }

        return $versionsSql;
    }
}
