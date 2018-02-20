<?php

namespace Oro\Bundle\ScopeBundle\Tests\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class LoadScopeData extends AbstractFixture
{
    const DEFAULT_SCOPE = 'default_scope';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $manager->getRepository(Scope::class)
            ->createQueryBuilder('s')
            ->delete()
            ->getQuery()
            ->execute();

        $scope = new Scope();
        $manager->persist($scope);
        $manager->flush();
        $this->setReference(self::DEFAULT_SCOPE, $scope);
    }
}
