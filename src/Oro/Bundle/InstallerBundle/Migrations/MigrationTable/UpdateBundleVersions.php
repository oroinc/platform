<?php
namespace Oro\Bundle\InstallerBundle\Migrations\MigrationTable;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;
use Oro\Bundle\InstallerBundle\Migrations\MigrationsLoader;

class UpdateBundleVersions implements Migration
{
    /**
     * @var array
     */
    protected $bundleVersions;

    /**
     * @param array $bundleVersions
     */
    public function setBundleVersions(array $bundleVersions)
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
