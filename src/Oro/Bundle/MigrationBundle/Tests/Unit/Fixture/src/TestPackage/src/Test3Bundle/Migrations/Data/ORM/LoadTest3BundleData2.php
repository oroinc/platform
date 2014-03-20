<?php
namespace Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\src\TestPackage\src\Test3Bundle\Migrations\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\MigrationBundle\Fixture\RequestVersionFixtureInterface;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

class LoadTest3BundleData2 extends AbstractFixture implements VersionedFixtureInterface, RequestVersionFixtureInterface
{
    public $dbVersion;

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.0';
    }

    /**
     * {@inheritdoc}
     */
    public function setDBVersion($version = null)
    {
        $this->dbVersion = $version;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
    }
}
