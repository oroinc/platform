<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Loader;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\MigrationBundle\Entity\DataFixture;
use Oro\Bundle\MigrationBundle\Migration\Loader\DataFixturesLoader;
use Oro\Bundle\MigrationBundle\Migration\RenameDataFixturesFixture;
use Oro\Bundle\MigrationBundle\Migration\UpdateDataFixturesFixture;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\TestPackageTest1Bundle;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test2Bundle\TestPackageTest2Bundle;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test3Bundle\TestPackageTest3Bundle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;

class DataFixturesLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $fixtureRepo;

    /** @var DataFixturesLoader */
    private $loader;

    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->fixtureRepo = $this->createMock(EntityRepository::class);
        $this->kernel = $this->createMock(Kernel::class);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->with(DataFixture::class)
            ->willReturn($this->fixtureRepo);

        $this->loader = new DataFixturesLoader($em, $this->kernel, $this->container);
    }

    /**
     * @dataProvider getFixturesProvider
     */
    public function testGetFixtures(array $bundles, array $loadedDataFixtureClasses, array $expectedFixtureClasses)
    {
        /** @var \Symfony\Component\HttpKernel\Bundle\Bundle $bundle */
        foreach ($bundles as $bundle) {
            $this->loader->loadFromDirectory(
                $bundle->getPath() . '/Migrations/Data/ORM'
            );
        }

        $loadedDataFixtures = [];
        foreach ($loadedDataFixtureClasses as $className => $version) {
            $loadedDataFixtures[] = $this->createDataFixture($className, $version);
        }

        $this->fixtureRepo->expects($this->any())
            ->method('findAll')
            ->willReturn($loadedDataFixtures);

        $fixtures = $this->loader->getFixtures();
        $fixtureClasses = $this->getFixturesClasses($fixtures);
        $this->assertEquals($expectedFixtureClasses, $fixtureClasses);
    }

    private function getFixturesClasses(array $fixtures): array
    {
        $result = [];
        foreach ($fixtures as $fixture) {
            $result[] = get_class($fixture);
        }

        return $result;
    }

    private function createDataFixture(string $className, string $version): DataFixture
    {
        $result = new DataFixture();
        $result->setClassName($className);
        $result->setVersion($version);

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getFixturesProvider(): array
    {
        $test1BundleNamespace = 'Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle';
        $test2BundleNamespace = 'Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test2Bundle';
        $test3BundleNamespace = 'Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test3Bundle';

        return [
            [
                [],
                [],
                []
            ],
            [
                [new TestPackageTest1Bundle()],
                [],
                [
                    $test1BundleNamespace . '\Migrations\Data\ORM\LoadTest1BundleData',
                    UpdateDataFixturesFixture::class,
                ]
            ],
            [
                [new TestPackageTest1Bundle()],
                [
                    $test1BundleNamespace . '\Migrations\Data\ORM\LoadTest1BundleData' => ''
                ],
                []
            ],
            [
                [new TestPackageTest2Bundle()],
                [],
                [
                    $test1BundleNamespace . '\Migrations\Data\ORM\LoadTest1BundleData',
                    $test2BundleNamespace . '\Migrations\Data\ORM\LoadTest2BundleData',
                    UpdateDataFixturesFixture::class,
                ]
            ],
            [
                [new TestPackageTest2Bundle()],
                [
                    $test1BundleNamespace . '\Migrations\Data\ORM\LoadTest1BundleData' => ''
                ],
                [
                    $test2BundleNamespace . '\Migrations\Data\ORM\LoadTest2BundleData',
                    UpdateDataFixturesFixture::class,
                ]
            ],
            [
                [new TestPackageTest2Bundle(), new TestPackageTest1Bundle()],
                [],
                [
                    $test1BundleNamespace . '\Migrations\Data\ORM\LoadTest1BundleData',
                    $test2BundleNamespace . '\Migrations\Data\ORM\LoadTest2BundleData',
                    UpdateDataFixturesFixture::class,
                ]
            ],
            [
                [new TestPackageTest2Bundle(), new TestPackageTest1Bundle()],
                [
                    $test2BundleNamespace . '\Migrations\Data\ORM\LoadTest2BundleData' => ''
                ],
                [
                    $test1BundleNamespace . '\Migrations\Data\ORM\LoadTest1BundleData',
                    UpdateDataFixturesFixture::class,
                ]
            ],
            [
                [new TestPackageTest3Bundle()],
                [],
                [
                    $test3BundleNamespace . '\Migrations\Data\ORM\LoadTest3BundleData1',
                    $test3BundleNamespace . '\Migrations\Data\ORM\LoadTest3BundleData2',
                    UpdateDataFixturesFixture::class,
                ]
            ],
            [
                [new TestPackageTest3Bundle()],
                [
                    $test3BundleNamespace . '\Migrations\Data\ORM\LoadTest3BundleData1' => '1.0',
                    $test3BundleNamespace . '\Migrations\Data\ORM\LoadTest3BundleData2' => '0.0'
                ],
                [
                    $test3BundleNamespace . '\Migrations\Data\ORM\LoadTest3BundleData2',
                    UpdateDataFixturesFixture::class,
                ]
            ],
            [
                [new TestPackageTest3Bundle()],
                [
                    $test3BundleNamespace . '\Migrations\Data\ORM\LoadTest3BundleData1' => '2.0',
                    $test3BundleNamespace . '\Migrations\Data\ORM\LoadTest3BundleData2' => '1.0'
                ],
                []
            ],
            'Rename and perform' => [
                [new TestPackageTest3Bundle()],
                [
                    $test3BundleNamespace . '\Migrations\Data\ORM\LoadTest3BundleData1OldName' => '0.0',
                    $test3BundleNamespace . '\Migrations\Data\ORM\LoadTest3BundleData2OldName' => '0.0'
                ],
                [
                    RenameDataFixturesFixture::class,
                    $test3BundleNamespace . '\Migrations\Data\ORM\LoadTest3BundleData1',
                    $test3BundleNamespace . '\Migrations\Data\ORM\LoadTest3BundleData2',
                    UpdateDataFixturesFixture::class,
                ]
            ],
            'Only rename' => [
                [new TestPackageTest3Bundle()],
                [
                    $test3BundleNamespace . '\Migrations\Data\ORM\LoadTest3BundleData1OldName' => '2.0',
                    $test3BundleNamespace . '\Migrations\Data\ORM\LoadTest3BundleData2OldName' => '1.0'
                ],
                [
                    RenameDataFixturesFixture::class,
                ]
            ],
        ];
    }
}
