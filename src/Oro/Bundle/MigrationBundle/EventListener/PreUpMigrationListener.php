<?php

namespace Oro\Bundle\MigrationBundle\EventListener;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;
use Oro\Bundle\MigrationBundle\Migration\CreateMigrationTableMigration;
use Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration;
use Oro\Bundle\MigrationBundle\Migration\UpdateEntityConfigMigration;

class PreUpMigrationListener
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @param PreMigrationEvent $event
     */
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
                $oroTableDataConfig = $this->kernel
                    ->locateResource('@OroMigrationBundle/EventListener/MigrationTableData/Oro.yml');
                $bundleVersions = Yaml::parse(realpath($oroTableDataConfig));

                $oroCrmTableDataConfig = $this->kernel
                    ->locateResource('@OroMigrationBundle/EventListener/MigrationTableData/OroCRM.yml');

                if ($event->isTableExist('orocrm_account')) {
                    $bundleVersions = array_merge(
                        $bundleVersions,
                        Yaml::parse(realpath($oroCrmTableDataConfig))
                    );
                }
                foreach ($bundleVersions as $bundleName => $version) {
                    $event->setLoadedVersion($bundleName, $version);
                }
                $event->addMigration(new UpdateBundleVersionMigration($bundleVersions));
                $event->addMigration(new UpdateEntityConfigMigration());
            }
        }
    }
}
