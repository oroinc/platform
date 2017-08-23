<?php

namespace Oro\Bundle\NavigationBundle\Builder;

use Knp\Menu\ItemInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Exception\MaxNestingLevelExceededException;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProviderInterface;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

class MenuUpdateBuilder implements BuilderInterface
{
    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var MenuUpdateProviderInterface $menuUpdateProvider
     */
    private $menuUpdateProvider;

    /**
     * @param LocalizationHelper          $localizationHelper
     * @param MenuUpdateProviderInterface $menuUpdateProvider
     */
    public function __construct(LocalizationHelper $localizationHelper, MenuUpdateProviderInterface $menuUpdateProvider)
    {
        $this->localizationHelper = $localizationHelper;
        $this->menuUpdateProvider = $menuUpdateProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ItemInterface $menu, array $options = [], $alias = null)
    {
        $menuUpdates = $this->menuUpdateProvider->getMenuUpdatesForMenuItem($menu, $options);

        foreach ($menuUpdates as $menuUpdate) {
            MenuUpdateUtils::updateMenuItem($menuUpdate, $menu, $this->localizationHelper, $options);
        }

        $this->applyDivider($menu);

        /** @var ItemInterface $item */
        foreach ($menu->getChildren() as $item) {
            $item = MenuUpdateUtils::getItemExceededMaxNestingLevel($menu, $item);
            if ($item) {
                throw new MaxNestingLevelExceededException(
                    sprintf(
                        "Item \"%s\" exceeded max nesting level in menu \"%s\".",
                        $item->getLabel(),
                        $menu->getLabel()
                    )
                );
            }
        }
    }

    /**
     * @param ItemInterface $item
     */
    private function applyDivider(ItemInterface $item)
    {
        if ($item->getExtra('divider', false)) {
            $class = trim(sprintf("%s %s", $item->getAttribute('class', ''), 'divider'));
            $item->setAttribute('class', $class);
        }

        foreach ($item->getChildren() as $child) {
            $this->applyDivider($child);
        }
    }
}
