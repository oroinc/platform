<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\ScopeBundle\Tests\DataFixtures\LoadScopeData;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadScopeUserData;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class MenuUpdateData extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;
    use MenuUpdateTrait;

    const MENU_UPDATE_1 = 'menu_update.1';
    const MENU_UPDATE_1_1 = 'menu_update.1_1';
    const MENU_UPDATE_2 = 'menu_update.2';
    const MENU_UPDATE_2_1 = 'menu_update.2_1';
    const MENU_UPDATE_2_1_1 = 'menu_update.2_1_1';
    const MENU_UPDATE_3 = 'menu_update.3';
    const MENU_UPDATE_3_1 = 'menu_update.3_1';

    /** @var array */
    protected static $menuUpdates = [
        self::MENU_UPDATE_1 => [
            'key' => self::MENU_UPDATE_1,
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
            'scope' => LoadScopeData::DEFAULT_SCOPE,
            'active' => true,
            'priority' => 10,
            'divider' => false,
            'custom' => true,
        ],
        self::MENU_UPDATE_1_1 => [
            'key' => self::MENU_UPDATE_1_1,
            'parent_key' => self::MENU_UPDATE_1,
            'default_title' => 'menu_update.1_1.title',
            'titles' => [],
            'default_description' => 'menu_update.1_1.description',
            'descriptions' => [],
            'uri' => '#menu_update.1_1',
            'menu' => 'application_menu',
            'scope' => LoadScopeData::DEFAULT_SCOPE,
            'active' => true,
            'priority' => 10,
            'divider' => false,
            'custom' => true,
        ],
        self::MENU_UPDATE_2 => [
            'key' => self::MENU_UPDATE_2,
            'parent_key' => null,
            'default_title' => 'menu_update.2.title',
            'titles' => [],
            'default_description' => 'menu_update.2.description',
            'descriptions' => [],
            'uri' => '#menu_update.2',
            'menu' => 'application_menu',
            'scope' => LoadScopeData::DEFAULT_SCOPE,
            'active' => false,
            'priority' => 10,
            'divider' => false,
            'custom' => true,
        ],
        self::MENU_UPDATE_2_1 => [
            'key' => self::MENU_UPDATE_2_1,
            'parent_key' => self::MENU_UPDATE_2,
            'default_title' => 'menu_update.2_1.title',
            'titles' => [],
            'default_description' => 'menu_update.2_1.description',
            'descriptions' => [],
            'uri' => '#menu_update.2_1',
            'menu' => 'application_menu',
            'scope' => LoadScopeData::DEFAULT_SCOPE,
            'active' => false,
            'priority' => 10,
            'divider' => false,
            'custom' => true,
        ],
        self::MENU_UPDATE_2_1_1 => [
            'key' => self::MENU_UPDATE_2_1_1,
            'parent_key' => self::MENU_UPDATE_2_1,
            'titles' => [],
            'descriptions' => [],
            'uri' => '#',
            'menu' => 'application_menu',
            'scope' => LoadScopeData::DEFAULT_SCOPE,
            'active' => false,
            'priority' => 10,
            'divider' => true,
            'custom' => true,
        ],
        self::MENU_UPDATE_3 => [
            'key' => self::MENU_UPDATE_3,
            'parent_key' => null,
            'default_title' => 'menu_update.3.title',
            'titles' => [],
            'default_description' => 'menu_update.3.description',
            'descriptions' => [],
            'uri' => '#menu_update.3',
            'menu' => 'application_menu',
            'scope' => LoadScopeUserData::SIMPLE_USER_SCOPE,
            'active' => true,
            'priority' => 20,
            'divider' => false,
            'custom' => true,
        ],
        self::MENU_UPDATE_3_1 => [
            'key' => self::MENU_UPDATE_3_1,
            'parent_key' => self::MENU_UPDATE_3,
            'default_title' => 'menu_update.3_1.title',
            'titles' => [],
            'default_description' => 'menu_update.3_1.description',
            'descriptions' => [],
            'uri' => '#menu_update.3_1',
            'menu' => 'application_menu',
            'scope' => LoadScopeUserData::SIMPLE_USER_SCOPE,
            'active' => true,
            'priority' => 10,
            'divider' => false,
            'custom' => true,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadLocalizationData::class,
            LoadUserData::class,
            LoadScopeData::class,
            LoadScopeUserData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$menuUpdates as $menuUpdateReference => $data) {
            $entity = $this->getMenuUpdate($data, MenuUpdate::class);
            $this->setReference($menuUpdateReference, $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
