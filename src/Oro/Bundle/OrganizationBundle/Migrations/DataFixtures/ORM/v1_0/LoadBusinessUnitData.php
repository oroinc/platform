<?php
namespace Oro\Bundle\OrganizationBundle\Migrations\DataFixtures\ORM\v1_0;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class LoadBusinessUnitData extends AbstractFixture implements DependentFixtureInterface
{
    const MAIN_BUSINESS_UNIT = 'Main';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\OrganizationBundle\Migrations\DataFixtures\ORM\v1_0\LoadOrganizationData'];
    }

    public function load(ObjectManager $manager)
    {
        $defaultBusinessUnit = new BusinessUnit();

        $defaultBusinessUnit
            ->setName(self::MAIN_BUSINESS_UNIT)
            ->setOrganization(
                $manager->getRepository('OroOrganizationBundle:Organization')->findOneBy(
                    ['name' => LoadOrganizationData::MAIN_ORGANIZATION]
                )
            );

        $this->addReference('default_business_unit', $defaultBusinessUnit);

        $manager->persist($defaultBusinessUnit);
        $manager->flush();
    }
}
