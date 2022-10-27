<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;

/**
 * Loads the first business unit from the database.
 */
class LoadBusinessUnit extends AbstractFixture implements InitialFixtureInterface
{
    public const BUSINESS_UNIT = 'business_unit';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $businessUnit = $manager->getRepository(BusinessUnit::class)
            ->createQueryBuilder('t')
            ->orderBy('t.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();
        $this->addReference(self::BUSINESS_UNIT, $businessUnit);
    }
}
