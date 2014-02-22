<?php

namespace Oro\Bundle\InstallerBundleTests\Unit\Migrations;

use Oro\Bundle\InstallerBundle\Migrations\MigrationTable\CreateMigrationTableMigration;
use Oro\Bundle\InstallerBundle\Tests\Unit\Fixture\src\TestPackage\src\Test1Bundle\TestPackageTest1Bundle;
use Oro\Bundle\InstallerBundle\Tests\Unit\Fixture\src\TestPackage\src\Test2Bundle\TestPackageTest2Bundle;

use Oro\Bundle\InstallerBundle\Migrations\MigrationsLoader;

class MigrationsLoaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var MigrationsLoader */
    protected $loader;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $kernel;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    public function setUp()
    {
        $this->kernel    = $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em         = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->connection));

        $this->loader = new MigrationsLoader($this->kernel, $this->em, $this->container);
    }

    /**
     * @dataProvider getMigrationsProvider
     */
    public function testGetMigrations($bundles, $installed, $expectedMigrationClasses)
    {
        $bundlesList     = [];
        /** @var \Symfony\Component\HttpKernel\Bundle\Bundle $bundle */
        foreach ($bundles as $bundle) {
            $bundlesList[$bundle->getName()] = $bundle;
        }

        $this->kernel->expects($this->any())
            ->method('getBundles')
            ->will($this->returnValue($bundlesList));

        $sm = $this->getMockForAbstractClass(
            'Doctrine\DBAL\Schema\AbstractSchemaManager',
            [],
            '',
            false,
            true,
            true,
            ['listTableNames']
        );
        $this->connection->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true));
        $this->connection->expects($this->any())
            ->method('getSchemaManager')
            ->will($this->returnValue($sm));
        $tableNames = [];
        if (null !== $installed) {
            $tableNames[] = CreateMigrationTableMigration::MIGRATION_TABLE;
            $this->connection->expects($this->once())
                ->method('fetchAll')
                ->with(
                    sprintf(
                        'select * from %s where id in (select max(id) from %s group by bundle)',
                        CreateMigrationTableMigration::MIGRATION_TABLE,
                        CreateMigrationTableMigration::MIGRATION_TABLE
                    )
                )
                ->will($this->returnValue($installed));
        } else {
            $this->connection->expects($this->never())
                ->method('fetchAll');
        }
        $sm->expects($this->once())
            ->method('listTableNames')
            ->will($this->returnValue($tableNames));

        $migrations = $this->loader->getMigrations();
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
                    'Oro\Bundle\InstallerBundle\Migrations\MigrationTable\CreateMigrationTableMigration',
                    'Migration\Test1BundleInstallation',
                    'Migration\v1_1\Test1BundleMigration11',
                    'Migration\v1_0\Test2BundleMigration10',
                    'Migration\v1_1\Test2BundleMigration11',
                    'Oro\Bundle\InstallerBundle\Migrations\MigrationTable\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest2Bundle(), new TestPackageTest1Bundle()],
                null,
                [
                    'Oro\Bundle\InstallerBundle\Migrations\MigrationTable\CreateMigrationTableMigration',
                    'Migration\v1_0\Test2BundleMigration10',
                    'Migration\v1_1\Test2BundleMigration11',
                    'Migration\Test1BundleInstallation',
                    'Migration\v1_1\Test1BundleMigration11',
                    'Oro\Bundle\InstallerBundle\Migrations\MigrationTable\UpdateBundleVersionMigration',
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
                    'Oro\Bundle\InstallerBundle\Migrations\MigrationTable\UpdateBundleVersionMigration',
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
                    'Oro\Bundle\InstallerBundle\Migrations\MigrationTable\UpdateBundleVersionMigration',
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
                    'Oro\Bundle\InstallerBundle\Migrations\MigrationTable\UpdateBundleVersionMigration',
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
                    'Oro\Bundle\InstallerBundle\Migrations\MigrationTable\UpdateBundleVersionMigration',
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
