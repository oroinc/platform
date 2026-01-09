<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Knp\Menu\ItemInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;

/**
 * Disables navigation items based on feature toggle configuration.
 *
 * This listener responds to menu configuration events and hides navigation items that have been
 * disabled through the feature toggle system. It supports multiple menu path separators to handle
 * different menu hierarchy notations and recursively disables parent items when all their children
 * are hidden, maintaining a clean menu structure without empty parent items.
 */
class NavigationItemsListener
{
    protected static $menuPathSeparators = [
        '.',
        ' > ',
    ];

    /** @var FeatureChecker */
    protected $featureChecker;

    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        $disabledItems = $this->featureChecker->getDisabledResourcesByType('navigation_items');
        if (!$disabledItems) {
            return;
        }

        $root = $event->getMenu();
        foreach (self::$menuPathSeparators as $menuPathSeparator) {
            foreach ($disabledItems as $disabledItem) {
                $path = explode($menuPathSeparator, $disabledItem);
                if ($root->getName() !== array_shift($path)) {
                    continue;
                }

                $item = $this->getItemByPath($root, $path);
                if ($item) {
                    $this->disableItem($item);
                }
            }
        }
    }

    protected function disableItem(ItemInterface $item)
    {
        $item->setDisplay(false);

        $parent = $item->getParent();
        if ($parent && $parent->getUri() === '#' && !$this->hasVisibleChildren($parent)) {
            $this->disableItem($parent);
        }
    }

    /**
     * @param ItemInterface $item
     *
     * @return boolean
     */
    protected function hasVisibleChildren(ItemInterface $item)
    {
        $children = $item->getChildren();
        foreach ($children as $child) {
            if ($child->isDisplayed()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ItemInterface $root
     * @param array $path
     *
     * @return ItemInterface|null
     */
    protected function getItemByPath(ItemInterface $root, array $path)
    {
        if (!$path) {
            return null;
        }

        foreach ($path as $nextPath) {
            if (null === ($root = $root->getChild($nextPath))) {
                return null;
            }
        }

        return $root;
    }
}
