<?php
namespace Oro\Bundle\UserBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\UserBundle\Entity\Group;

class LoadGroupData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData'];
    }

    public function load(ObjectManager $manager)
    {
        /**
         * addRole was commented due a ticket BAP-1675
         */
        $defaultBusinessUnit = $manager
            ->getRepository('OroOrganizationBundle:BusinessUnit')
            ->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_BUSINESS_UNIT]);
        $administrators = new Group('Administrators');
        //$administrators->addRole($this->getReference('administrator_role'));
        if ($defaultBusinessUnit) {
            $administrators->setOwner($defaultBusinessUnit);
        }
        $manager->persist($administrators);

        $sales= new Group('Sales');
        //$sales->addRole($this->getReference('manager_role'));
        if ($defaultBusinessUnit) {
            $sales->setOwner($defaultBusinessUnit);
        }
        $manager->persist($sales);

        $marketing= new Group('Marketing');
        //$marketing->addRole($this->getReference('manager_role'));
        if ($defaultBusinessUnit) {
            $marketing->setOwner($defaultBusinessUnit);
        }
        $manager->persist($marketing);

        $manager->flush();
    }
}
