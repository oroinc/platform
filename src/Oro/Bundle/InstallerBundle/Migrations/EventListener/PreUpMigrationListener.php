<?php

namespace Oro\Bundle\InstallerBundle\Migrations\EventListener;

use Symfony\Component\Yaml\Yaml;
use Oro\Bundle\InstallerBundle\Migrations\Event\PreMigrationEvent;
use Oro\Bundle\InstallerBundle\Migrations\MigrationTable\CreateMigrationTableMigration;
use Oro\Bundle\InstallerBundle\Migrations\MigrationTable\UpdateBundleVersionMigration;

class PreUpMigrationListener
{
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

            // load MigrationTable initial data for BAP and OroCRM bundles installed before migrations is introduced
            // @todo: this transient solution can be removed in a future
            // when we ensure RC1 and RC2 are updated for all clients
            if ($event->isTableExist('oro_installer_bundle_version')) {
                $bundleVersions = Yaml::parse(realpath(__DIR__ . '/MigrationTableData/Oro.yml'));
                if ($event->isTableExist('orocrm_account')) {
                    $bundleVersions = array_merge(
                        $bundleVersions,
                        Yaml::parse(realpath(__DIR__ . '/MigrationTableData/OroCRM.yml'))
                    );
                }
                foreach ($bundleVersions as $bundleName => $version) {
                    $event->setLoadedVersion($bundleName, $version);
                }
                $event->addMigration(new UpdateBundleVersionMigration($bundleVersions));
            }
        }
    }
}
