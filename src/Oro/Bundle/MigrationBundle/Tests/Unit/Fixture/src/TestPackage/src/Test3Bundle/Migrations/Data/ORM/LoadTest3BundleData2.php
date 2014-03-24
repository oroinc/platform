<?php
namespace Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\src\TestPackage\src\Test3Bundle\Migrations\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\MigrationBundle\Fixture\LoadedFixtureVersionAwareInterface;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

class LoadTest3BundleData2 extends AbstractFixture implements
    VersionedFixtureInterface,
    LoadedFixtureVersionAwareInterface,
    OrderedFixtureInterface
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
    public function setLoadedVersion($version = null)
    {
        $this->dbVersion = $version;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }
}
