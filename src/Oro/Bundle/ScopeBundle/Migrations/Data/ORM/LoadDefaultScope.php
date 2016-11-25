<?php

namespace Oro\Bundle\ScopeBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class LoadDefaultScope extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $scope = new Scope();
        $manager->persist($scope);
        $manager->flush();
    }
}
