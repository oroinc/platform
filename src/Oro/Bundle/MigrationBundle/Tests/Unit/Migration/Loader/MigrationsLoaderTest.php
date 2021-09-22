<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Loader;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Event\MigrationEvents;
use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;
use Oro\Bundle\MigrationBundle\Migration\Loader\MigrationsLoader;
use Oro\Bundle\MigrationBundle\Migration\MigrationState;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\TestPackageTest1Bundle;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test2Bundle\TestPackageTest2Bundle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Kernel;

class MigrationsLoaderTest extends \PHPUnit\Framework\TestCase
{
    private MigrationsLoader $loader;

    private Kernel|\PHPUnit\Framework\MockObject\MockObject $kernel;

    private ContainerInterface|\PHPUnit\Framework\MockObject\MockObject $container;

    private EventDispatcher|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private Connection|\PHPUnit\Framework\MockObject\MockObject $connection;

    protected function setUp(): void
    {
        $this->kernel          = $this->createMock(Kernel::class);
        $this->container       = $this->getMockForAbstractClass(ContainerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);

        $this->connection = $this->createMock(Connection::class);

        $this->loader = new MigrationsLoader(
            $this->kernel,
            $this->connection,
            $this->container,
            $this->eventDispatcher
        );
    }

    /**
     * @dataProvider getMigrationsProvider
     */
    public function testGetMigrations($bundles, $installed, $expectedMigrationClasses): void
    {
        $bundlesList = [];
        /** @var \Symfony\Component\HttpKernel\Bundle\Bundle $bundle */
        foreach ($bundles as $bundle) {
            $bundlesList[$bundle->getName()] = $bundle;
        }

        $this->kernel->expects($this->any())
            ->method('getBundles')
            ->willReturn($bundlesList);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(
                function ($event, $eventName) use (&$installed) {
                    if ($eventName === MigrationEvents::PRE_UP && null !== $installed) {
                        foreach ($installed as $val) {
                            /** @var PreMigrationEvent $event */
                            $event->setLoadedVersion($val['bundle'], $val['version']);
                        }
                    }

                    return $event;
                }
            );

        $migrations       = $this->loader->getMigrations();
        $migrationClasses = $this->getMigrationClasses($migrations);
        $this->assertEquals($expectedMigrationClasses, $migrationClasses);
    }

    /**
     * @param MigrationState[] $migrations
     *
     * @return string[]
     */
    protected function getMigrationClasses(array $migrations): array
    {
        return array_map(
            function ($migration) {
                return get_class($migration->getMigration());
            },
            $migrations
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getMigrationsProvider(): array
    {
        $testPackage = 'Oro\\Bundle\\MigrationBundle\\Tests\\Unit\\Fixture\\TestPackage\\';
        $test1Bundle = $testPackage . 'Test1Bundle\\Migrations\\Schema';
        $test2Bundle = $testPackage . 'Test2Bundle\\Migrations\\Schema';

        return [
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                null,
                [
                    $test1Bundle . '\Test1BundleInstallation',
                    $test1Bundle . '\v1_1\Test1BundleMigration11',
                    $test2Bundle . '\v1_0\Test2BundleMigration10',
                    $test2Bundle . '\v1_0\Test2BundleMigration11',
                    $test2Bundle . '\v1_1\Test2BundleMigration12',
                    $test2Bundle . '\v1_1\Test2BundleMigration11',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest2Bundle(), new TestPackageTest1Bundle()],
                null,
                [
                    $test2Bundle . '\v1_0\Test2BundleMigration10',
                    $test2Bundle . '\v1_0\Test2BundleMigration11',
                    $test2Bundle . '\v1_1\Test2BundleMigration12',
                    $test2Bundle . '\v1_1\Test2BundleMigration11',
                    $test1Bundle . '\Test1BundleInstallation',
                    $test1Bundle . '\v1_1\Test1BundleMigration11',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                [],
                [
                    $test1Bundle . '\Test1BundleInstallation',
                    $test1Bundle . '\v1_1\Test1BundleMigration11',
                    $test2Bundle . '\v1_0\Test2BundleMigration10',
                    $test2Bundle . '\v1_0\Test2BundleMigration11',
                    $test2Bundle . '\v1_1\Test2BundleMigration12',
                    $test2Bundle . '\v1_1\Test2BundleMigration11',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                [
                    ['bundle' => 'TestPackageTest1Bundle', 'version' => null],
                ],
                [
                    $test1Bundle . '\Test1BundleInstallation',
                    $test1Bundle . '\v1_1\Test1BundleMigration11',
                    $test2Bundle . '\v1_0\Test2BundleMigration10',
                    $test2Bundle . '\v1_0\Test2BundleMigration11',
                    $test2Bundle . '\v1_1\Test2BundleMigration12',
                    $test2Bundle . '\v1_1\Test2BundleMigration11',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                [
                    ['bundle' => 'TestPackageTest1Bundle', 'version' => 'v1_0'],
                ],
                [
                    $test1Bundle . '\v1_1\Test1BundleMigration11',
                    $test2Bundle . '\v1_0\Test2BundleMigration10',
                    $test2Bundle . '\v1_0\Test2BundleMigration11',
                    $test2Bundle . '\v1_1\Test2BundleMigration12',
                    $test2Bundle . '\v1_1\Test2BundleMigration11',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                [
                    ['bundle' => 'TestPackageTest1Bundle', 'version' => 'v1_0'],
                    ['bundle' => 'TestPackageTest2Bundle', 'version' => 'v1_0'],
                ],
                [
                    $test1Bundle . '\v1_1\Test1BundleMigration11',
                    $test2Bundle . '\v1_1\Test2BundleMigration12',
                    $test2Bundle . '\v1_1\Test2BundleMigration11',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                [
                    ['bundle' => 'TestPackageTest1Bundle', 'version' => 'v1_1'],
                    ['bundle' => 'TestPackageTest2Bundle', 'version' => 'v1_1'],
                ],
                [
                    'Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
        ];
    }
}
