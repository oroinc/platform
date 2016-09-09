<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

class MenuUpdateData extends AbstractFixture
{
    use UserUtilityTrait;

    const MENU = 'default_menu';
    const ORGANIZATION = 'default_organization';
    const BUSINESS_UNIT = 'default_unit';
    const USER = 'default_user';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->addReference(
            self::ORGANIZATION,
            $manager->getRepository('OroOrganizationBundle:Organization')->getFirst()
        );
        $this->addReference(
            self::BUSINESS_UNIT,
            $manager->getRepository('OroOrganizationBundle:BusinessUnit')->getFirst()
        );
        $this->addReference(
            self::USER,
            $this->getFirstUser($manager)
        );

        $updatesData = [
            [
                'ownershipType' => MenuUpdate::OWNERSHIP_GLOBAL,
                'ownerId' => null,
                'key' => 'activity'
            ],
            [
                'ownershipType' => MenuUpdate::OWNERSHIP_ORGANIZATION,
                'ownerId' => $this->getReference(self::ORGANIZATION)->getId(),
                'key' => 'product'
            ],
            [
                'ownershipType' => MenuUpdate::OWNERSHIP_BUSINESS_UNIT,
                'ownerId' => $this->getReference(self::BUSINESS_UNIT)->getId(),
                'key' => 'quotes'
            ],
            [
                'ownershipType' => MenuUpdate::OWNERSHIP_USER,
                'ownerId' => $this->getReference(self::USER)->getId(),
                'key' => 'lists'
            ],
        ];
        foreach ($updatesData as $updateData) {
            $update = new MenuUpdate();
            $update
                ->setKey($updateData['key'])
                ->setMenu(self::MENU)
                ->setOwnershipType($updateData['ownershipType'])
                ->setOwnerId($updateData['ownerId'])
            ;
            $manager->persist($update);
        }

        $manager->flush();
    }
}
