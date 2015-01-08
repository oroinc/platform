<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

class UpdateBundleVersionMigration implements Migration, FailIndependentMigration
{
    /** @var MigrationState[] */
    protected $migrations;

    /**
     * @param MigrationState[] $migrations
     */
    public function __construct(array $migrations)
    {
        $this->migrations = $migrations;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $bundleVersions = $this->getLatestSuccessMigrationVersions();
        if (!empty($bundleVersions)) {
            $date = new \DateTime();
            foreach ($bundleVersions as $bundleName => $bundleVersion) {
                $sql = sprintf(
                    "INSERT INTO %s (bundle, version, loaded_at) VALUES ('%s', '%s', '%s')",
                    CreateMigrationTableMigration::MIGRATION_TABLE,
                    $bundleName,
                    $bundleVersion,
                    $date->format('Y-m-d H:i:s')
                );
                $queries->addQuery($sql);
            }
        }
    }

    /**
     * Extracts latest version of successfully finished migrations for each bundle
     *
     * @return string[]
     *      key   = bundle name
     *      value = version
     */
    protected function getLatestSuccessMigrationVersions()
    {
        $result = [];
        foreach ($this->migrations as $migration) {
            if (!$migration->isSuccessful() || !$migration->getVersion()) {
                continue;
            }
            if (isset($result[$migration->getBundleName()])) {
                if (version_compare($result[$migration->getBundleName()], $migration->getVersion()) === -1) {
                    $result[$migration->getBundleName()] = $migration->getVersion();
                }
            } else {
                $result[$migration->getBundleName()] = $migration->getVersion();
            }
        }

        return $result;
    }
}
