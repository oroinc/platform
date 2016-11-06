<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Loads the first user from the database.
 */
class LoadUser extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = $manager->getRepository('OroUserBundle:User')
            ->createQueryBuilder('t')
            ->orderBy('t.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();
        $this->addReference('user', $organization);
    }
}
