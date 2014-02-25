<?php

namespace Oro\Bundle\InstallerBundle\Migrations\EventListener;

use Symfony\Component\Yaml\Yaml;
use Oro\Bundle\InstallerBundle\Migrations\Event\PreMigrationEvent;
use Oro\Bundle\InstallerBundle\Migrations\MigrationTable\CreateMigrationTableMigration;
use Oro\Bundle\InstallerBundle\Migrations\MigrationTable\UpdateBundleVersionMigration;

class PreUpMigrationListener
{
    protected $transitiveData = [
        '/MigrationTableData/Oro.yml',
        '/MigrationTableData/OroCRM.yml',
    ];

    public function onPreUp(PreMigrationEvent $event)
    {
        if ($event->isTableExist(CreateMigrationTableMigration::MIGRATION_TABLE)) {
            $data = $event->getData(
                sprintf(
                    'select * from %s where id in (select max(id) from %s group by bundle)',
                    CreateMigrationTableMigration::MIGRATION_TABLE,
                    CreateMigrationTableMigration::MIGRATION_TABLE
                )
            );
            foreach ($data as $val) {
                $event->setLoadedVersion($val['bundle'], $val['version']);
            }
        } else {
            $event->addMigration(new CreateMigrationTableMigration());

            if ($event->isTableExist('oro_installer_bundle_version')) {
                $bundleVersions = [];
                foreach ($this->transitiveData as $relationPath) {
                    $bundleVersions = array_merge($bundleVersions, Yaml::parse(realpath(__DIR__ . $relationPath)));
                }
                foreach ($bundleVersions as $bundleName => $version) {
                    $event->setLoadedVersion($bundleName, $version);
                }
                $event->addMigration(new UpdateBundleVersionMigration($bundleVersions));
            }
        }
    }
}
