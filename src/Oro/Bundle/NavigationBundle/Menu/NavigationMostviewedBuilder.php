<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\ItemInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;

class NavigationMostviewedBuilder extends NavigationItemBuilder
{
    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Modify menu by adding, removing or editing items.
     *
     * @param \Knp\Menu\ItemInterface $menu
     * @param array                   $options
     * @param string|null             $alias
     */
    public function build(ItemInterface $menu, array $options = array(), $alias = null)
    {
        $options['order_by'] = array(array('field' => NavigationHistoryItem::NAVIGATION_HISTORY_COLUMN_VISIT_COUNT));
        $maxItems = $this->configManager->get('oro_navigation.max_items');
        if (!is_null($maxItems)) {
            $options['max_items'] = $maxItems;
        }
        parent::build($menu, $options, $alias);
    }

    /**
     * @inheritdoc
     */
    protected function getMatchedRoute($item)
    {
        return isset($item['route']) ? $item['route'] : null;
    }
}
