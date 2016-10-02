<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Model\GlobalOwnershipProvider;
use Oro\Bundle\NavigationBundle\Model\UserOwnershipProvider;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

class MenuUpdateData extends AbstractFixture
{
    use UserUtilityTrait;

    const MENU = 'default_menu';
    const ORGANIZATION = 'default_organization';
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
            self::USER,
            $this->getFirstUser($manager)
        );

        $updatesData = [
            [
                'ownershipType' => GlobalOwnershipProvider::TYPE,
                'ownerId' => $this->getReference(self::ORGANIZATION)->getId(),
                'key' => 'product'
            ],
            [
                'ownershipType' => UserOwnershipProvider::TYPE,
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
