<?php

namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\UserBundle\Entity\Group;

/**
 * Loads user groups.
 */
class LoadGroupData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadOrganizationAndBusinessUnitData::class];
    }

    public function load(ObjectManager $manager): void
    {
        $defaultBusinessUnit = $manager->getRepository(BusinessUnit::class)
            ->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_BUSINESS_UNIT]);
        $manager->persist($this->createGroup('Administrators', $defaultBusinessUnit));
        $manager->persist($this->createGroup('Sales', $defaultBusinessUnit));
        $manager->persist($this->createGroup('Marketing', $defaultBusinessUnit));
        $manager->flush();
    }

    private function createGroup(string $name, ?BusinessUnit $businessUnit): Group
    {
        $group = new Group($name);
        if (null !== $businessUnit) {
            $group->setOwner($businessUnit);
        }

        return $group;
    }
}
