<?php

namespace Oro\Bundle\UserBundle\Menu;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;

/**
 * Allows to add extra items to user menu
 */
class UserMenuBuilder implements BuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function build(ItemInterface $menu, array $options = [], $alias = null)
    {
        $menu->setExtra('type', 'user_menu');

        $menu
            ->addChild(
                'divider-user-before-logout',
                [
                    'extras' => [
                        MenuUpdateInterface::IS_DIVIDER => true,
                        MenuUpdateInterface::POSITION => 90,
                    ],
                ]
            )
            ->setLabel('');

        $menu
            ->addChild(
                'Logout',
                [
                    'route' => 'oro_user_security_logout',
                    'linkAttributes' => [
                        'class' => 'no-hash'
                    ],
                    'extras' => [
                        MenuUpdateInterface::POSITION => 100,
                    ],
                ]
            );
    }
}
