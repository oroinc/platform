<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadBusinessUnitData extends AbstractFixture implements DependentFixtureInterface
{
    public const BUSINESS_UNIT_1 = 'business_unit_1';
    public const BUSINESS_UNIT_2 = 'business_unit_2';

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $businessUnits = [self::BUSINESS_UNIT_1, self::BUSINESS_UNIT_2];
        foreach ($businessUnits as $businessUnitName) {
            $businessUnit = new BusinessUnit();
            $businessUnit->setName($businessUnitName);
            $businessUnit->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $manager->persist($businessUnit);
            $this->setReference($businessUnitName, $businessUnit);
        }
        $manager->flush();
    }
}
