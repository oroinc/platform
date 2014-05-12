<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration\Loader;

use Oro\Bundle\MigrationBundle\Entity\DataFixture;
use Oro\Bundle\MigrationBundle\Migration\Loader\DataFixturesLoader;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\TestPackageTest1Bundle;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test2Bundle\TestPackageTest2Bundle;
use Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test3Bundle\TestPackageTest3Bundle;

class DataFixturesLoaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var DataFixturesLoader */
    protected $loader;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $fixtureRepo;

    public function setUp()
    {
        $this->container = $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->fixtureRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em         = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getRepository')
            ->with('OroMigrationBundle:DataFixture')
            ->will($this->returnValue($this->fixtureRepo));

        $this->loader = new DataFixturesLoader($this->em, $this->container);
    }

    /**
     * @dataProvider getFixturesProvider
     */
    public function testGetFixtures($bundles, $loadedDataFixtureClasses, $expectedFixtureClasses)
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
            ->will($this->returnValue($loadedDataFixtures));

        $fixtures = $this->loader->getFixtures();
        $fixtureClasses = $this->getFixturesClasses($fixtures);
        $this->assertEquals($expectedFixtureClasses, $fixtureClasses);
    }

    protected function getFixturesClasses(array $fixtures)
    {
        $result = [];
        foreach ($fixtures as $fixture) {
            $result[] = get_class($fixture);
        }

        return $result;
    }

    /**
     * @param string $className
     * @param string $version
     * @return DataFixture
     */
    protected function createDataFixture($className, $version)
    {
        $result = new DataFixture();
        $result->setClassName($className);
        $result->setVersion($version);

        return $result;
    }

    public function getFixturesProvider()
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
                    $test1BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest1BundleData',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateDataFixturesFixture',
                ]
            ],
            [
                [new TestPackageTest1Bundle()],
                [
                    $test1BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest1BundleData' => ''
                ],
                []
            ],
            [
                [new TestPackageTest2Bundle()],
                [],
                [
                    $test1BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest1BundleData',
                    $test2BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest2BundleData',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateDataFixturesFixture',
                ]
            ],
            [
                [new TestPackageTest2Bundle()],
                [
                    $test1BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest1BundleData' => ''
                ],
                [
                    $test2BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest2BundleData',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateDataFixturesFixture',
                ]
            ],
            [
                [new TestPackageTest2Bundle(), new TestPackageTest1Bundle()],
                [],
                [
                    $test1BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest1BundleData',
                    $test2BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest2BundleData',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateDataFixturesFixture',
                ]
            ],
            [
                [new TestPackageTest2Bundle(), new TestPackageTest1Bundle()],
                [
                    $test2BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest2BundleData' => ''
                ],
                [
                    $test1BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest1BundleData',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateDataFixturesFixture',
                ]
            ],
            [
                [new TestPackageTest3Bundle()],
                [],
                [
                    $test3BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest3BundleData1',
                    $test3BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest3BundleData2',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateDataFixturesFixture',
                ]
            ],
            [
                [new TestPackageTest3Bundle()],
                [
                    $test3BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest3BundleData1' => '1.0',
                    $test3BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest3BundleData2' => '0.0'
                ],
                [
                    $test3BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest3BundleData2',
                    'Oro\Bundle\MigrationBundle\Migration\UpdateDataFixturesFixture',
                ]
            ],
            [
                [new TestPackageTest3Bundle()],
                [
                    $test3BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest3BundleData1' => '2.0',
                    $test3BundleNamespace . '\Migrations\DataFixtures\ORM\LoadTest3BundleData2' => '1.0'
                ],
                []
            ]
        ];
    }
}
