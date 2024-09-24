<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test3Bundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Fixture\RenamedFixtureInterface;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

class LoadTest3BundleData1 extends AbstractFixture implements
    VersionedFixtureInterface,
    OrderedFixtureInterface,
    RenamedFixtureInterface
{
    #[\Override]
    public function getVersion()
    {
        return '1.0';
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
    }

    #[\Override]
    public function getOrder()
    {
        return 1;
    }

    #[\Override]
    public function getPreviousClassNames(): array
    {
        return [
            self::class . 'OldName',
        ];
    }
}
