<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Oro\Bundle\MigrationBundle\Event\MigrationEvent;
use Oro\Bundle\MigrationBundle\Event\MigrationEvents;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\Loader\MigrationsLoader;
use Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @group install
 */
class InstallerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testInstallersAreUpToDateWithMigrations()
    {
        /** @var MigrationsLoader $loader */
        $loader = $this->getContainer()->get('oro_migration.migrations.loader');

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->getContainer()->get('event_dispatcher');
        $closure = function (MigrationEvent $event) {
            $event->stopPropagation();
        };

        $eventDispatcher->addListener(MigrationEvents::PRE_UP, $closure, PHP_INT_MAX);
        $eventDispatcher->addListener(MigrationEvents::POST_UP, $closure, PHP_INT_MAX);
        $this->assertMigrations($loader);

        $eventDispatcher->removeListener(MigrationEvents::PRE_UP, $closure);
        $eventDispatcher->removeListener(MigrationEvents::POST_UP, $closure);
    }

    /**
     * @param MigrationsLoader $loader
     */
    protected function assertMigrations(MigrationsLoader $loader)
    {
        $notCoveredMigrations = [];
        $migrationStates = $loader->getMigrations();
        foreach ($migrationStates as $migrationState) {
            if (is_a($migrationState->getMigration(), UpdateBundleVersionMigration::class)) {
                continue;
            }

            if (!is_a($migrationState->getMigration(), Installation::class)) {
                $notCoveredMigrations[] = get_class($migrationState->getMigration());
            }
        }

        $this->assertEmpty($notCoveredMigrations, implode("\n", $notCoveredMigrations));
    }
}
