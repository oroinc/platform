<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\NavigationBundle\Entity\NavigationItem;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class NavigationItemData extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    public const NAVIGATION_ITEM_PINBAR_1 = 'item_1';
    public const NAVIGATION_ITEM_PINBAR_2 = 'item_2';
    public const NAVIGATION_ITEM_PINBAR_3 = 'item_3';

    /** @var array */
    private static $navigationItems = [
        self::NAVIGATION_ITEM_PINBAR_1 => [
            'type' => 'pinbar',
            'url' => '/admin/config/system',
            'title' => [
                'template' => 'oro.config.menu.system_configuration.label - oro.user.menu.system_tab.label',
                'short_template' => 'oro.config.menu.system_configuration.label',
                'params' => [],
            ],
            'position' => 0,
        ],
        self::NAVIGATION_ITEM_PINBAR_2 => [
            'type' => 'pinbar',
            'url' => '/admin/user/view/1',
            'title' => [
                'template' => '%username% - oro.ui.view - oro.user.entity_plural_label - '
                    . 'oro.user.menu.users_management.label - oro.user.menu.system_tab.label',
                'short_template' => '%username% - oro.ui.view',
                'params' => ['%username%' => 'John Doe'],
            ],
            'position' => 1,
        ],
        self::NAVIGATION_ITEM_PINBAR_3 => [
            'type' => 'pinbar',
            'url' => '/admin/user',
            'title' => [
                'template' => 'oro.user.entity_plural_label - oro.user.menu.users_management.label - '
                    . 'oro.user.menu.system_tab.label',
                'short_template' => 'oro.user.entity_plural_label',
                'params' => [],
            ],
            'position' => 2,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadUserData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$navigationItems as $key => $data) {
            $user = $this->getFirstUser($manager);

            $data['title'] = json_encode($data);

            $entity = new NavigationItem($data + [
                    'user' => $user,
                    'organization' => $this->getReference('organization'),
                ]);
            $entity->setType($data['type']);

            $this->setReference($key, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
