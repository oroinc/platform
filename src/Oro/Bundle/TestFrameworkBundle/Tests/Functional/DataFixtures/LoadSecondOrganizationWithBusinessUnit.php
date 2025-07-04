<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;

class LoadSecondOrganizationWithBusinessUnit extends AbstractFixture implements InitialFixtureInterface
{
    public const SECOND_ORGANIZATION = 'second_organization';
    public const SECOND_ORGANIZATION_BUSINESS_UNIT = 'second_organization_business_unit';

    public function load(ObjectManager $manager)
    {
        $businessUnit = new BusinessUnit();
        $businessUnit->setName(self::SECOND_ORGANIZATION_BUSINESS_UNIT);

        $organization = new Organization();
        $organization->setName(self::SECOND_ORGANIZATION);
        $organization->addBusinessUnit($businessUnit);
        $businessUnit->setOrganization($organization);

        $manager->persist($businessUnit);
        $manager->persist($organization);

        $this->addReference(self::SECOND_ORGANIZATION_BUSINESS_UNIT, $businessUnit);
        $this->addReference(self::SECOND_ORGANIZATION, $organization);

        $manager->flush();
    }
}
