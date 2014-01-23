<?php
namespace Oro\Bundle\OrganizationBundle\DataFixtures\Migrations\ORM\v1_0;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class LoadBusinessUnitData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\OrganizationBundle\DataFixtures\Migrations\ORM\v1_0\LoadOrganizationData'];
    }

    public function load(ObjectManager $manager)
    {
        $defaultBusinessUnit = new BusinessUnit();

        $defaultBusinessUnit
            ->setName('Main')
            ->setOrganization(
                $manager->getRepository('OroOrganizationBundle:Organization')->findOneBy(['name' => 'default'])
            );

        $this->addReference('default_business_unit', $defaultBusinessUnit);

        $manager->persist($defaultBusinessUnit);
        $manager->flush();
    }
}
