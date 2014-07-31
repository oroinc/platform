<?php
namespace Oro\Bundle\OrganizationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class LoadOrganizationAndBusinessUnitData extends AbstractFixture
{
    const MAIN_ORGANIZATION = 'default';
    const MAIN_BUSINESS_UNIT = 'Main';

    public function load(ObjectManager $manager)
    {
        // load default organization
        $defaultOrganization = new Organization();
        $defaultOrganization
            ->setName(self::MAIN_ORGANIZATION)
            ->setEnabled(true);
        $this->addReference('default_organization', $defaultOrganization);
        $manager->persist($defaultOrganization);

        // load default business unit
        $defaultBusinessUnit = new BusinessUnit();
        $defaultBusinessUnit
            ->setName(self::MAIN_BUSINESS_UNIT)
            ->setOrganization($defaultOrganization);
        $this->addReference('default_business_unit', $defaultBusinessUnit);
        $manager->persist($defaultBusinessUnit);

        $manager->flush();
    }
}
