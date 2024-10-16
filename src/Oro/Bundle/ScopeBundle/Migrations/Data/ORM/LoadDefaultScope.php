<?php

namespace Oro\Bundle\ScopeBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class LoadDefaultScope extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $scope = new Scope();
        $manager->persist($scope);
        $manager->flush();
    }
}
