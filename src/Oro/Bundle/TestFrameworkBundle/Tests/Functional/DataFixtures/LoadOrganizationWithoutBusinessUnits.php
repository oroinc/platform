<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;

class LoadOrganizationWithoutBusinessUnits extends AbstractFixture implements InitialFixtureInterface
{
    public const ORGANIZATION_WITHOUT_BUSINESS_UNITS = 'organization_without_business_units';

    public function load(ObjectManager $manager)
    {
        $organization = new Organization();
        $organization->setName(self::ORGANIZATION_WITHOUT_BUSINESS_UNITS);

        $manager->persist($organization);

        $this->addReference(self::ORGANIZATION_WITHOUT_BUSINESS_UNITS, $organization);

        $manager->flush();
    }
}
