<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Knp\Menu\ItemInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;

class NavigationItemsListener
{
    protected static $menuPathSeparators = [
        '.',
        ' > ',
    ];

    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
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

    /**
     * @param ItemInterface $item
     */
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
