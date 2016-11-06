<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Loads the first organization from the database.
 */
class LoadOrganization extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')
            ->createQueryBuilder('t')
            ->orderBy('t.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();
        $this->addReference('organization', $organization);
    }
}
