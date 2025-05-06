<?php

declare(strict_types=1);

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Command\Stubs\application\migrations\Stub\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadAppDemoDataStub implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        // Stub
    }
}
