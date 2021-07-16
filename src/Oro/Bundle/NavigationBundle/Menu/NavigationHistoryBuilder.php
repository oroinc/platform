<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\Util\MenuManipulator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NavigationBundle\Provider\NavigationItemsProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Builds menu from navigation history items.
 */
class NavigationHistoryBuilder extends NavigationItemBuilder
{
    /** @var Matcher */
    private $matcher;

    /** @var MenuManipulator */
    private $menuManipulator;

    /** @var ConfigManager */
    private $configManager;

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        NavigationItemsProviderInterface $navigationItemsProvider,
        Matcher $matcher,
        MenuManipulator $menuManipulator,
        ConfigManager $configManager
    ) {
        parent::__construct($tokenAccessor, $navigationItemsProvider);

        $this->configManager = $configManager;
        $this->matcher = $matcher;
        $this->menuManipulator = $menuManipulator;
    }

    /**
     * Modify menu by adding, removing or editing items.
     *
     * @param \Knp\Menu\ItemInterface $menu
     * @param array $options
     * @param string|null $alias
     */
    public function build(ItemInterface $menu, array $options = [], $alias = null)
    {
        $maxItems = $this->configManager->get('oro_navigation.max_items');

        if ($maxItems !== null) {
            // we'll hide current item, so always select +1 item
            $options['max_items'] = $maxItems + 1;
        }

        parent::build($menu, $options, $alias);

        $children = $menu->getChildren();
        foreach ($children as $child) {
            if ($this->matcher->isCurrent($child)) {
                $menu->removeChild($child);

                break;
            }
        }

        $this->menuManipulator->slice($menu, 0, $maxItems);
    }
}
