<?php
namespace Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\src\TestPackage\src\Test2Bundle\Migrations\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class LoadTest2BundleData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\src\TestPackage\src'
            . '\Test1Bundle\Migrations\DataFixtures\ORM\LoadTest1BundleData'
        ];
    }

    public function load(ObjectManager $manager)
    {
    }
}
