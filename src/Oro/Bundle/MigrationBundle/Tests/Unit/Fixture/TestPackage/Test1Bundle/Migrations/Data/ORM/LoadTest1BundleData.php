<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class LoadTest1BundleData extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
    }
}
