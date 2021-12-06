<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\NavigationBundle\Entity\NavigationItem;
use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\LoadUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NavigationItemData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const NAVIGATION_ITEM_PINBAR_1 = 'item_1';
    public const NAVIGATION_ITEM_PINBAR_2 = 'item_2';
    public const NAVIGATION_ITEM_PINBAR_3 = 'item_3';

    public const NAVIGATION_ITEM_FAVORITE_1 = 'item_favorite_1';

    public static array $navigationItems = [
        self::NAVIGATION_ITEM_PINBAR_1 => [
            'type' => 'pinbar',
            'route' => 'oro_config_configuration_system',
            'title' => [
                'template' => 'oro.config.menu.system_configuration.label - oro.user.menu.system_tab.label',
                'short_template' => 'oro.config.menu.system_configuration.label',
                'params' => [],
            ],
            'position' => 0,
        ],
        self::NAVIGATION_ITEM_PINBAR_2 => [
            'type' => 'pinbar',
            'route' => 'oro_user_index',
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
            'route' => 'oro_user_role_index',
            'title' => [
                'template' => 'oro.user.entity_plural_label - oro.user.menu.users_management.label - '
                    . 'oro.user.menu.system_tab.label',
                'short_template' => 'oro.user.entity_plural_label',
                'params' => [],
            ],
            'position' => 2,
        ],
        self::NAVIGATION_ITEM_FAVORITE_1 => [
            'type' => 'favorite',
            'route' => 'oro_user_role_create',
            'title' => 'Roles',
            'position' => 0,
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
        $user = $this->getReference(LoadUserData::USER_NAME_2);
        $urlGenerator = $this->container->get(UrlGeneratorInterface::class);

        foreach (self::$navigationItems as $key => $data) {
            $data['title'] = json_encode($data, JSON_THROW_ON_ERROR);

            $data['url'] = $urlGenerator->generate($data['route'], ['restore' => 1]);
            unset($data['route']);

            $entity = new NavigationItem(
                $data + [
                    'user' => $user,
                    'organization' => $user->getOrganization(),
                ]
            );
            $entity->setType($data['type']);

            $this->setReference($key, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
