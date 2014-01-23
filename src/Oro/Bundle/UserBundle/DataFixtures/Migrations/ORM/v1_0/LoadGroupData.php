<?php
namespace Oro\Bundle\UserBundle\DataFixtures\Migrations\ORM\v1_0;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\UserBundle\Entity\Group;

class LoadGroupData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\OrganizationBundle\DataFixtures\Migrations\ORM\v1_0\LoadBusinessUnitData'];
    }

    public function load(ObjectManager $manager)
    {
        /**
         * addRole was commented due a ticket BAP-1675
         */
        $defaultBusinessUnit = null;
        if ($this->hasReference('default_business_unit')) {
            $defaultBusinessUnit = $manager
                ->getRepository('OroOrganizationBundle:BusinessUnit')
                ->findOneBy(['name' => 'Main']);
        }
        $administrators = new Group('Administrators');
        //$administrators->addRole($this->getReference('administrator_role'));
        if ($defaultBusinessUnit) {
            $administrators->setOwner($defaultBusinessUnit);
        }
        $manager->persist($administrators);
        $this->setReference('oro_group_administrators', $administrators);

        $sales= new Group('Sales');
        //$sales->addRole($this->getReference('manager_role'));
        if ($defaultBusinessUnit) {
            $sales->setOwner($defaultBusinessUnit);
        }
        $manager->persist($sales);
        $this->setReference('oro_group_sales', $sales);

        $marketing= new Group('Marketing');
        //$marketing->addRole($this->getReference('manager_role'));
        if ($defaultBusinessUnit) {
            $marketing->setOwner($defaultBusinessUnit);
        }
        $manager->persist($marketing);
        $this->setReference('oro_group_marketing', $marketing);

        $manager->flush();
    }
}
