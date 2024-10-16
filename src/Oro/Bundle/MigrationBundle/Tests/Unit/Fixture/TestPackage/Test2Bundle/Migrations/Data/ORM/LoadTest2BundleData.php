<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test2Bundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadTest2BundleData extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies()
    {
        return [
            'Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage'
            . '\Test1Bundle\Migrations\Data\ORM\LoadTest1BundleData'
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
    }
}
