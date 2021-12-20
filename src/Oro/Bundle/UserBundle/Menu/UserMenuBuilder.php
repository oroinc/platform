<?php

namespace Oro\Bundle\UserBundle\Menu;

use Knp\Menu\ItemInterface;
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
        $menu->addChild('divider-user-before-logout')
            ->setLabel('')
            ->setExtra('divider', true);
        $menu->addChild(
            'Logout',
            [
                'route' => 'oro_user_security_logout',
                'linkAttributes' => [
                    'class' => 'no-hash'
                ]
            ]
        );
    }
}
