<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadBusinessUnitData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadOrganization::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $businessUnit = new BusinessUnit();
        $businessUnit->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
        $businessUnit->setName('TestBusinessUnit');
        $businessUnit->setEmail('test@mail.com');
        $manager->persist($businessUnit);
        $this->setReference('TestBusinessUnit', $businessUnit);
        $manager->flush();
    }
}
