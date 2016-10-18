<?php

namespace Oro\Bundle\NavigationBundle\Builder;

use Knp\Menu\ItemInterface;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Exception\MaxNestingLevelExceededException;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\NavigationBundle\Menu\Provider\OwnershipProviderInterface;

class MenuUpdateBuilder implements BuilderInterface
{
    const OWNERSHIP_TYPE_OPTION = 'ownershipType';

    /** @var array - an array of OwnershipProviders grouped by area and priority
     * Example:
     * [
     *     'default' => [
     *         100 => [
     *             'global' => $globalProvider
     *         ],
     *         200 => [
     *             'organization' => $organizationProvider
     *         ],
     *         300 => [
     *             'user' => $userProvider
     *         ],
     *      ],
     *     'custom' => [
     *         100 => [
     *             'global' => $globalProvider
     *         ],
     *         200 => [
     *             'foo' => $fooProvider,
     *             'bar' => $barProvider
     *         ],
     *     ]
     * ]
     *
     */
    private $providers = [];
    
    /** @var LocalizationHelper */
    private $localizationHelper;

    /**
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ItemInterface $menu, array $options = [], $alias = null)
    {
        $ownershipType = array_key_exists(self::OWNERSHIP_TYPE_OPTION, $options) ?
            $options[self::OWNERSHIP_TYPE_OPTION] : null;
        $area = $menu->getExtra('area', ConfigurationBuilder::DEFAULT_AREA);
        $menuName = $menu->getName();
        $updates = $this->getUpdates($area, $menuName, $ownershipType);
        foreach ($updates as $update) {
            if ($update->getMenu() == $menuName) {
                MenuUpdateUtils::updateMenuItem($update, $menu, $this->localizationHelper);
            }
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
     * @param OwnershipProviderInterface $provider
     * @param string                     $area
     * @param integer                    $priority
     * @return MenuUpdateBuilder
     */
    public function addProvider(OwnershipProviderInterface $provider, $area, $priority)
    {
        $this->providers[$area][$priority][$provider->getType()] = $provider;

        return $this;
    }

    /**
     * @param string $area
     * @param string $type
     * @return null|OwnershipProviderInterface
     */
    public function getProvider($area, $type)
    {
        $providers = $this->getProviders($area);

        return isset($providers[$type]) ? $providers[$type] : null;
    }

    /**
     * @param string      $area
     * @param string      $menuName
     * @param string|null $ownershipType
     * @return array
     */
    public function getUpdates($area, $menuName, $ownershipType = null)
    {
        $providers = $this->getProviders($area, $ownershipType);

        $menuUpdates = [];
        foreach ($providers as $ownershipProvider) {
            $result = $ownershipProvider->getMenuUpdates($menuName);
            $menuUpdates = array_merge($menuUpdates, $result);
        }

        return $menuUpdates;
    }

    /**
     * Return ordered list of ownership providers started by $ownershipType
     * @param string      $area
     * @param string|null $ownershipType
     * @return OwnershipProviderInterface[]
     */
    private function getProviders($area, $ownershipType = null)
    {
        if (!isset($this->providers[$area])) {
            return [];
        }
        $providers = $this->providers[$area];
        // convert prioritised list to flat ordered list
        ksort($providers, SORT_NUMERIC);
        $filteredProviders = [];
        foreach ($providers as $list) {
            $filteredProviders = array_merge($filteredProviders, $list);
        }
        // return all tree if ownershipType not defined
        if (null === $ownershipType) {
            return array_reverse($filteredProviders);
        }
        // remove ownerships higher than selected
        $key = array_search($ownershipType, array_keys($filteredProviders), true);
        if ($key !== false) {
            return array_reverse(array_slice($filteredProviders, $key, null, true));
        }

        return [];
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
