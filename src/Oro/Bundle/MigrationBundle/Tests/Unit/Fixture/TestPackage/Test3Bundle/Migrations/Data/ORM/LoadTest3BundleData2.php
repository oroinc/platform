<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test3Bundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Fixture\LoadedFixtureVersionAwareInterface;
use Oro\Bundle\MigrationBundle\Fixture\RenamedFixtureInterface;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

class LoadTest3BundleData2 extends AbstractFixture implements
    VersionedFixtureInterface,
    LoadedFixtureVersionAwareInterface,
    OrderedFixtureInterface,
    RenamedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        return '1.0';
    }

    /**
     * {@inheritDoc}
     */
    public function setLoadedVersion($version = null): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder(): int
    {
        return 2;
    }

    /**
     * {@inheritDoc}
     */
    public function getPreviousClassNames(): array
    {
        return [
            self::class . 'OldName',
        ];
    }
}
