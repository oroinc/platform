<?php

namespace Oro\Bundle\NavigationBundle\Builder;

use Knp\Menu\ItemInterface;

use Oro\Bundle\NavigationBundle\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
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

    /** @var MenuUpdateHelper */
    protected $menuUpdateHelper;

    /**
     * @param MenuUpdateHelper $menuUpdateHelper
     */
    public function __construct(MenuUpdateHelper $menuUpdateHelper)
    {
        $this->menuUpdateHelper = $menuUpdateHelper;
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
        foreach ($this->getUpdates($area, $menuName, $ownershipType) as $update) {
            if ($update->getMenu() == $menuName) {
                $this->menuUpdateHelper->updateMenuItem($update, $menu);
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
     * @return \Oro\Bundle\NavigationBundle\Menu\Provider\OwnershipProviderInterface[]
     */
    protected function getProviders($area, $ownershipType = null)
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
            return $filteredProviders;
        }
        // remove ownerships higher than selected
        $key = array_search($ownershipType, array_keys($filteredProviders), true);
        if ($key !== false) {
            return array_slice($filteredProviders, $key, null, true);
        }

        return [];
    }

}
