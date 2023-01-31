<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\ItemInterface;

/**
 * Hides menu items with children that are not displayed or not allowed.
 */
class HideEmptyItemsBuilder implements BuilderInterface
{
    public function build(ItemInterface $menu, array $options = [], $alias = null): void
    {
        $this->applyRecursively($menu);
    }

    private function applyRecursively(ItemInterface $menuItem): void
    {
        if ($menuItem->getDisplayChildren() && $menuItem->getChildren()
            && in_array($menuItem->getUri(), [null, '#'], true)) {
            $isDisplayed = false;
            foreach ($menuItem->getChildren() as $childMenuItem) {
                $this->applyRecursively($childMenuItem);

                $isDisplayed = $isDisplayed
                    || ($childMenuItem->isDisplayed() && $childMenuItem->getExtra('isAllowed', false));
            }

            if (!$isDisplayed) {
                $menuItem->setDisplay(false);
            }
        }
    }
}
