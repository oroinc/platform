<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

/**
 * Loads demo organization - ACME with appropriate business unit
 */
class LoadAcmeOrganizationAndBusinessUnitData extends AbstractFixture
{
    use UserUtilityTrait;

    const REFERENCE_DEMO_ORGANIZATION = 'demo_organization';
    const REFERENCE_DEMO_BU = 'demo_business_unit';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $defaultOrganization = new Organization();
        $defaultOrganization
            ->setName('ACME, Inc')
            ->setEnabled(true);

        $manager->persist($defaultOrganization);
        $this->setReference(self::REFERENCE_DEMO_ORGANIZATION, $defaultOrganization);

        $defaultBusinessUnit = new BusinessUnit();
        $defaultBusinessUnit
            ->setName('Main Acme,Inc')
            ->setOrganization($defaultOrganization);
        $manager->persist($defaultBusinessUnit);
        $this->setReference(self::REFERENCE_DEMO_BU, $defaultBusinessUnit);
        //Sets organization and bu relation to admin user
        $admin = $this->getFirstUser($manager);
        $admin->addOrganization($defaultOrganization)->addBusinessUnit($defaultBusinessUnit);

        $manager->flush();
    }
}
