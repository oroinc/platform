<?php

namespace Oro\Bundle\InstallerBundleTests\Unit\Migrations;

use Oro\Bundle\InstallerBundle\Tests\Unit\Fixture\src\TestPackage\src\Test1Bundle\TestPackageTest1Bundle;
use Oro\Bundle\InstallerBundle\Tests\Unit\Fixture\src\TestPackage\src\Test2Bundle\TestPackageTest2Bundle;

use Oro\Bundle\InstallerBundle\Migrations\MigrationsLoader;

class MigrationsLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MigrationsLoader
     */
    protected $loader;

    protected $kernel;

    protected $em;

    protected $container;

    protected $connection;

    public function setUp()
    {
        $this->kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->container =$this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->connection =
        $bundles = [
            new TestPackageTest1Bundle(),
            new TestPackageTest2Bundle()
        ];

        $this->kernel->expects($this->any())
            ->method('getBundles')
            ->will($this->returnValue($bundles));

        $this->loader = new MigrationsLoader($this->kernel, $this->em, $this->container);
    }

    public function testGetMigrations()
    {
        $migrations = $this->loader->getMigrations();
        var_dump($migrations);
    }
}
