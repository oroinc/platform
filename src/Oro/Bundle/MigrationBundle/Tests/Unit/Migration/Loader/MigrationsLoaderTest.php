<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Loader;

use Oro\Bundle\MigrationBundle\Event\MigrationEvents;
use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\src\TestPackage\src\Test1Bundle\TestPackageTest1Bundle;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\src\TestPackage\src\Test2Bundle\TestPackageTest2Bundle;

use Oro\Bundle\MigrationBundle\Migration\Loader\MigrationsLoader;

class MigrationsLoaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var MigrationsLoader */
    protected $loader;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $kernel;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    public function setUp()
    {
        $this->kernel          = $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->getMock();
        $this->container       = $this->getMockForAbstractClass(
            'Symfony\Component\DependencyInjection\ContainerInterface'
        );
        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

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
    public function testGetMigrations($bundles, $installed, $expectedMigrationClasses)
    {
        $bundlesList = [];
        /** @var \Symfony\Component\HttpKernel\Bundle\Bundle $bundle */
        foreach ($bundles as $bundle) {
            $bundlesList[$bundle->getName()] = $bundle;
        }

        $this->kernel->expects($this->any())
            ->method('getBundles')
            ->will($this->returnValue($bundlesList));

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->will(
                $this->returnCallback(
                    function ($eventName, $event) use (&$installed) {
                        if ($eventName === MigrationEvents::PRE_UP) {
                            if (null !== $installed) {
                                foreach ($installed as $val) {
                                    /** @var PreMigrationEvent $event */
                                    $event->setLoadedVersion($val['bundle'], $val['version']);
                                }
                            }
                        }
                    }
                )
            );

        $migrations       = $this->loader->getMigrations();
        $migrationClasses = $this->getMigrationClasses($migrations);
        $this->assertEquals($expectedMigrationClasses, $migrationClasses);
    }

    protected function getMigrationClasses(array $migrations)
    {
        return array_map(
            function ($migration) {
                return get_class($migration);
            },
            $migrations
        );
    }

    public function getMigrationsProvider()
    {
        return [
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                null,
                [
                    'Migration\Test1BundleInstallation',
                    'Migration\v1_1\Test1BundleMigration11',
                    'Migration\v1_0\Test2BundleMigration10',
                    'Migration\v1_1\Test2BundleMigration11',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest2Bundle(), new TestPackageTest1Bundle()],
                null,
                [
                    'Migration\v1_0\Test2BundleMigration10',
                    'Migration\v1_1\Test2BundleMigration11',
                    'Migration\Test1BundleInstallation',
                    'Migration\v1_1\Test1BundleMigration11',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                [],
                [
                    'Migration\Test1BundleInstallation',
                    'Migration\v1_1\Test1BundleMigration11',
                    'Migration\v1_0\Test2BundleMigration10',
                    'Migration\v1_1\Test2BundleMigration11',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                [
                    ['bundle' => 'TestPackageTest1Bundle', 'version' => null],
                ],
                [
                    'Migration\Test1BundleInstallation',
                    'Migration\v1_1\Test1BundleMigration11',
                    'Migration\v1_0\Test2BundleMigration10',
                    'Migration\v1_1\Test2BundleMigration11',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                [
                    ['bundle' => 'TestPackageTest1Bundle', 'version' => 'v1_0'],
                ],
                [
                    'Migration\v1_1\Test1BundleMigration11',
                    'Migration\v1_0\Test2BundleMigration10',
                    'Migration\v1_1\Test2BundleMigration11',
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
                    'Migration\v1_1\Test1BundleMigration11',
                    'Migration\v1_1\Test2BundleMigration11',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                [
                    ['bundle' => 'TestPackageTest1Bundle', 'version' => 'v1_1'],
                    ['bundle' => 'TestPackageTest2Bundle', 'version' => 'v1_1'],
                ],
                []
            ],
        ];
    }
}
