<?php
namespace Oro\Bundle\UserBundle\Migrations\DataFixtures\ORM\v1_0;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\OrganizationBundle\Migrations\DataFixtures\ORM\v1_0\LoadBusinessUnitData;

class LoadGroupData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\OrganizationBundle\Migrations\DataFixtures\ORM\v1_0\LoadBusinessUnitData'];
    }

    public function load(ObjectManager $manager)
    {
        /**
         * addRole was commented due a ticket BAP-1675
         */
        $defaultBusinessUnit = $manager
            ->getRepository('OroOrganizationBundle:BusinessUnit')
            ->findOneBy(['name' => LoadBusinessUnitData::MAIN_BUSINESS_UNIT]);
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
