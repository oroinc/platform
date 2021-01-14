<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;

/**
 * Loads the first organization from the database.
 */
class LoadOrganization extends AbstractFixture implements InitialFixtureInterface
{
    public const ORGANIZATION = 'organization';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = $manager->getRepository(Organization::class)
            ->createQueryBuilder('t')
            ->orderBy('t.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();
        $this->addReference(self::ORGANIZATION, $organization);
    }
}
