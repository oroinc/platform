<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Component\Config\Resolver\ResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Builds menu items based on configuration.
 */
class ConfigurationBuilder implements BuilderInterface
{
    public const MAX_NESTING_LEVEL = 'max_nesting_level';

    const DEFAULT_SCOPE_TYPE = 'menu_default_visibility';

    /** @var ResolverInterface */
    protected $resolver;

    /** @var EventDispatcherInterface */
    private $factory;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var ConfigurationProvider */
    protected $configurationProvider;

    public function __construct(
        ResolverInterface $resolver,
        FactoryInterface $factory,
        EventDispatcherInterface $eventDispatcher,
        ConfigurationProvider $configurationProvider
    ) {
        $this->resolver = $resolver;
        $this->factory = $factory;
        $this->eventDispatcher = $eventDispatcher;
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * Modify menu by adding, removing or editing items.
     *
     * @param ItemInterface $menu
     * @param array         $options
     * @param string|null   $alias
     */
    public function build(ItemInterface $menu, array $options = [], $alias = null)
    {
        $tree = $this->configurationProvider->getMenuTree();

        if (array_key_exists($alias, $tree)) {
            $treeData = $tree[$alias];

            if (!empty($treeData['extras'])) {
                $menu->setExtras($treeData['extras']);
            }

            $this->setExtraFromConfig($menu, $treeData, 'type');
            $this->setExtraFromConfig($menu, $treeData, 'scope_type', ConfigurationBuilder::DEFAULT_SCOPE_TYPE);
            $this->setExtraFromConfig($menu, $treeData, 'read_only', false);
            $this->setExtraFromConfig($menu, $treeData, self::MAX_NESTING_LEVEL, 0);

            $existingNames[$alias] = true;
            $this->appendChildData($menu, $treeData['children'], $options, $existingNames);
        }

        $event = new ConfigureMenuEvent($this->factory, $menu);
        $this->eventDispatcher->dispatch($event, ConfigureMenuEvent::getEventName($alias));
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function appendChildData(ItemInterface $menu, array $sliceData, array $options, array &$existingNames)
    {
        $items = $this->configurationProvider->getMenuItems();

        foreach ($sliceData as $itemName => $itemData) {
            // Throw exception if duplicated item name was found in menu tree
            if (array_key_exists($itemName, $existingNames)) {
                $rootName = $menu->getRoot()->getName();
                $message = sprintf('Item key "%s" duplicated in tree menu "%s".', $itemName, $rootName);
                throw new \InvalidArgumentException($message);
            }

            // Reserve item name in existing names list
            $existingNames[$itemName] = true;

            // Get additional options from navigation.yml config
            $additionalOptions = array_key_exists($itemName, $items) ? $items[$itemName] : [];

            // Override item name if it contains in additional options
            if (empty($additionalOptions['name'])) {
                $additionalOptions['name'] = $itemName;
            }

            $this->moveToExtras($additionalOptions, 'position', true);
            $this->moveToExtras($additionalOptions, 'translateDomain');
            $this->moveToExtras($additionalOptions, 'translateParameters');
            $this->moveToExtras($additionalOptions, 'translate_disabled');
            $this->moveToExtras($additionalOptions, 'acl_resource_id');

            // Create new child menu item and append root menu options
            $newMenuItem = $menu->addChild($additionalOptions['name'], array_merge($additionalOptions, $options));
            if (!empty($itemData['children'])) {
                $this->appendChildData($newMenuItem, $itemData['children'], $options, $existingNames);
            }
        }
    }

    /**
     * @param ItemInterface $menu
     * @param array         $config
     * @param string        $optionName
     * @param mixed         $default
     */
    private function setExtraFromConfig($menu, $config, $optionName, $default = null)
    {
        if (!empty($config[$optionName])) {
            $menu->setExtra($optionName, $config[$optionName]);
        } elseif ($default !== null) {
            $menu->setExtra($optionName, $default);
        }
    }

    /**
     * @param array  $menuItem
     * @param string $optionName
     * @param bool $preferValueFromExtras
     *
     * @return void
     */
    private function moveToExtras(array &$menuItem, $optionName, $preferValueFromExtras = false)
    {
        if (isset($menuItem[$optionName])) {
            if (!isset($menuItem['extras'][$optionName]) || !$preferValueFromExtras) {
                $menuItem['extras'][$optionName] = $menuItem[$optionName];
            }
            unset($menuItem[$optionName]);
        }
    }
}
