<?php
namespace Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\src\TestPackage\src\Test1Bundle\Migrations\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadTest1BundleData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
    }
}
