<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\ItemInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Provider\NavigationItemsProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Build menu from most viewed navigation items.
 */
class NavigationMostviewedBuilder extends NavigationItemBuilder
{
    /** @var ConfigManager */
    private $configManager;

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        NavigationItemsProviderInterface $navigationItemsProvider,
        ConfigManager $configManager
    ) {
        parent::__construct($tokenAccessor, $navigationItemsProvider);

        $this->configManager = $configManager;
    }

    /**
     * Modify menu by adding, removing or editing items.
     *
     * @param \Knp\Menu\ItemInterface $menu
     * @param array $options
     * @param string|null $alias
     */
    public function build(ItemInterface $menu, array $options = array(), $alias = null)
    {
        $options['order_by'] = array(array('field' => NavigationHistoryItem::NAVIGATION_HISTORY_COLUMN_VISIT_COUNT));
        $maxItems = $this->configManager->get('oro_navigation.max_items');
        if ($maxItems !== null) {
            $options['max_items'] = $maxItems;
        }

        parent::build($menu, $options, $alias);
    }
}
