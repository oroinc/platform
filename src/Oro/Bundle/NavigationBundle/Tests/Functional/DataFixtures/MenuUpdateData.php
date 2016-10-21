<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Menu\Provider\GlobalOwnershipProvider;
use Oro\Bundle\NavigationBundle\Menu\Provider\UserOwnershipProvider;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

use Oro\Component\Testing\Unit\EntityTrait;

class MenuUpdateData extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;
    use EntityTrait;

    /** @var array */
    protected static $menuUpdates = [
        'menu_update.1' => [
            'key' => 'menu_update.1',
            'parent_key' => null,
            'default_title' => 'menu_update.1.title',
            'titles' => [
                'en_US' => 'menu_update.1.title.en_US',
                'en_CA' => 'menu_update.1.title.en_CA',
            ],
            'default_description' => 'menu_update.1.description',
            'descriptions' => [
                'en_US' => 'menu_update.1.description.en_US',
                'en_CA' => 'menu_update.1.description.en_CA',
            ],
            'uri' => '#menu_update.1',
            'menu' => 'application_menu',
            'ownership_type' => GlobalOwnershipProvider::TYPE,
            'owner_id' => 0,
            'active' => true,
            'priority' => 10,
            'divider' => false,
            'custom' => true,
        ],
        'menu_update.1_1' => [
            'key' => 'menu_update.1_1',
            'parent_key' => 'menu_update.1',
            'default_title' => 'menu_update.1_1.title',
            'titles' => [],
            'default_description' => 'menu_update.1_1.description',
            'descriptions' => [],
            'uri' => '#menu_update.1_1',
            'menu' => 'application_menu',
            'ownership_type' => GlobalOwnershipProvider::TYPE,
            'owner_id' => 0,
            'active' => true,
            'priority' => 10,
            'divider' => false,
            'custom' => true,
        ],
        'menu_update.2' => [
            'key' => 'menu_update.2',
            'parent_key' => null,
            'default_title' => 'menu_update.2.title',
            'titles' => [],
            'default_description' => 'menu_update.2.description',
            'descriptions' => [],
            'uri' => '#menu_update.2',
            'menu' => 'application_menu',
            'ownership_type' => GlobalOwnershipProvider::TYPE,
            'owner_id' => 0,
            'active' => false,
            'priority' => 10,
            'divider' => false,
            'custom' => true,
        ],
        'menu_update.2_1' => [
            'key' => 'menu_update.2_1',
            'parent_key' => 'menu_update.2',
            'default_title' => 'menu_update.2_1.title',
            'titles' => [],
            'default_description' => 'menu_update.2_1.description',
            'descriptions' => [],
            'uri' => '#menu_update.2_1',
            'menu' => 'application_menu',
            'ownership_type' => GlobalOwnershipProvider::TYPE,
            'owner_id' => 0,
            'active' => false,
            'priority' => 10,
            'divider' => false,
            'custom' => true,
        ],
        'menu_update.2_1_1' => [
            'key' => 'menu_update.2_1_1',
            'parent_key' => 'menu_update.2_1',
            'titles' => [],
            'descriptions' => [],
            'uri' => '#',
            'menu' => 'application_menu',
            'ownership_type' => GlobalOwnershipProvider::TYPE,
            'owner_id' => 0,
            'active' => false,
            'priority' => 10,
            'divider' => true,
            'custom' => true,
        ],
        'menu_update.3' => [
            'key' => 'menu_update.3',
            'parent_key' => null,
            'default_title' => 'menu_update.3.title',
            'titles' => [],
            'default_description' => 'menu_update.3.description',
            'descriptions' => [],
            'uri' => '#menu_update.3',
            'menu' => 'application_menu',
            'ownership_type' => UserOwnershipProvider::TYPE,
            'owner_id' => 1,
            'active' => true,
            'priority' => 20,
            'divider' => false,
            'custom' => true,
        ],
        'menu_update.4' => [
            'key' => 'menu_update.4',
            'parent_key' => null,
            'default_title' => 'menu_update.4.title',
            'titles' => [],
            'default_description' => 'menu_update.4.description',
            'descriptions' => [],
            'uri' => '#menu_update.4',
            'menu' => 'shortcuts',
            'ownership_type' => GlobalOwnershipProvider::TYPE,
            'owner_id' => 10,
            'active' => true,
            'priority' => 10,
            'divider' => false,
            'custom' => true,
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData',
            'Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$menuUpdates as $menuUpdateReference => $data) {
            $titles = $data['titles'];
            unset($data['titles']);

            $descriptions = $data['descriptions'];
            unset($data['descriptions']);

            $entity = $this->getEntity(MenuUpdate::class, $data);

            foreach ($titles as $localization => $title) {
                $fallbackValue = new LocalizedFallbackValue();
                $fallbackValue
                    ->setLocalization($this->getReference($localization))
                    ->setString($title);

                $entity->addTitle($fallbackValue);
            }

            foreach ($descriptions as $localization => $description) {
                $fallbackValue = new LocalizedFallbackValue();
                $fallbackValue
                    ->setLocalization($this->getReference($localization))
                    ->setText($description);

                $entity->addDescription($fallbackValue);
            }

            $this->setReference($menuUpdateReference, $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }

//    const MENU = 'default_menu';
//    const OTHER_MENU = 'other_menu';
//    const ORGANIZATION = 'default_organization';
//    const USER = 'default_user';
//
//    /**
//     * {@inheritdoc}
//     */
//    public function load(ObjectManager $manager)
//    {
//        $this->addReference(
//            self::ORGANIZATION,
//            $manager->getRepository('OroOrganizationBundle:Organization')->getFirst()
//        );
//        $this->addReference(
//            self::USER,
//            $this->getFirstUser($manager)
//        );
//
//        $updatesData = [
//            [
//                'ownershipType' => GlobalOwnershipProvider::TYPE,
//                'ownerId' => $this->getReference(self::ORGANIZATION)->getId(),
//                'key' => 'product',
//                'menu' => self::MENU,
//            ],
//            [
//                'ownershipType' => UserOwnershipProvider::TYPE,
//                'ownerId' => $this->getReference(self::USER)->getId(),
//                'key' => 'lists',
//                'menu' => self::MENU,
//            ],
//        ];
//        foreach ($updatesData as $updateData) {
//            $update = new MenuUpdate();
//            $update
//                ->setKey($updateData['key'])
//                ->setMenu($updateData['menu'])
//                ->setOwnershipType($updateData['ownershipType'])
//                ->setOwnerId($updateData['ownerId'])
//            ;
//            $manager->persist($update);
//        }
//
//        $manager->flush();
//    }
}
