<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\ItemInterface;

/**
 * Appends "divider" class to the "class" menu item attributes if menu item has "divider" extra option.
 */
class DividerBuilder implements BuilderInterface
{
    public function build(ItemInterface $menu, array $options = [], $alias = null): void
    {
        $this->applyRecursively($menu);
    }

    private function applyRecursively(ItemInterface $menuItem): void
    {
        if (!$menuItem->isDisplayed()) {
            return;
        }

        if ($menuItem->getExtra('divider', false)) {
            $class = trim(sprintf("%s %s", $menuItem->getAttribute('class', ''), 'divider'));
            $menuItem->setAttribute('class', $class);
        }

        foreach ($menuItem->getChildren() as $childMenuItem) {
            $this->applyRecursively($childMenuItem);
        }
    }
}
