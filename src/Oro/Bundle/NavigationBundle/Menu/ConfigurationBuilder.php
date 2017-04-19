<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\NavigationBundle\Config\MenuConfiguration;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;

use Oro\Component\Config\Resolver\ResolverInterface;

class ConfigurationBuilder implements BuilderInterface
{
    const DEFAULT_SCOPE_TYPE = 'menu_default_visibility';

    /** @var ResolverInterface */
    protected $resolver;

    /** @var EventDispatcherInterface */
    private $factory;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var MenuConfiguration */
    protected $menuConfiguration;

    /**
     * @param ResolverInterface        $resolver
     * @param FactoryInterface         $factory
     * @param EventDispatcherInterface $eventDispatcher
     * @param MenuConfiguration        $menuConfiguration
     */
    public function __construct(
        ResolverInterface $resolver,
        FactoryInterface $factory,
        EventDispatcherInterface $eventDispatcher,
        MenuConfiguration $menuConfiguration
    ) {
        $this->resolver = $resolver;
        $this->factory = $factory;
        $this->eventDispatcher = $eventDispatcher;
        $this->menuConfiguration = $menuConfiguration;
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
        $tree = $this->menuConfiguration->getTree();
        $items = $this->menuConfiguration->getItems();

        if (!empty($tree) && !empty($items)) {
            foreach ($tree as $menuTreeName => $menuTreeElement) {
                if ($menuTreeName == $alias) {
                    if (!empty($menuTreeElement['extras'])) {
                        $menu->setExtras($menuTreeElement['extras']);
                    }

                    $defaultArea = ConfigurationBuilder::DEFAULT_SCOPE_TYPE;
                    $this->setExtraFromConfig($menu, $menuTreeElement, 'type');
                    $this->setExtraFromConfig($menu, $menuTreeElement, 'scope_type', $defaultArea);
                    $this->setExtraFromConfig($menu, $menuTreeElement, 'read_only', false);
                    $this->setExtraFromConfig($menu, $menuTreeElement, 'max_nesting_level', 0);

                    $this->createFromArray($menu, $menuTreeElement['children'], $items, $options);
                }
            }
        }

        $event = new ConfigureMenuEvent($this->factory, $menu);
        $this->eventDispatcher->dispatch(ConfigureMenuEvent::getEventName($alias), $event);
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
     * @param ItemInterface $menu
     * @param array         $data
     * @param array         $itemList
     * @param array         $options
     * @param array         $itemCodes
     */
    private function createFromArray(
        ItemInterface $menu,
        array $data,
        array &$itemList,
        array $options = [],
        array &$itemCodes = []
    ) {
        $isAllowed = false;
        foreach ($data as $itemCode => $itemData) {
            if (in_array($itemCode, $itemCodes, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Item key "%s" duplicated in tree menu "%s".',
                    $itemCode,
                    $menu->getRoot()->getName()
                ));
            }
            $itemCodes[] = $itemCode;

            $itemData = $this->resolver->resolve($itemData);
            if (!empty($itemList[$itemCode])) {
                $itemOptions = $itemList[$itemCode];

                if (empty($itemOptions['name'])) {
                    $itemOptions['name'] = $itemCode;
                }

                $this->moveToExtras($itemOptions, 'position', true);
                $this->moveToExtras($itemOptions, 'translateDomain');
                $this->moveToExtras($itemOptions, 'translateParameters');
                $this->moveToExtras($itemOptions, 'translate_disabled');
                $this->moveToExtras($itemOptions, 'acl_resource_id');

                $newMenuItem = $menu->addChild($itemOptions['name'], array_merge($itemOptions, $options));

                if (!empty($itemData['children'])) {
                    $this->createFromArray($newMenuItem, $itemData['children'], $itemList, $options, $itemCodes);
                }

                $isAllowed = $isAllowed || $newMenuItem->getExtra('isAllowed');
            }
        }

        if ($menu->getExtra('isAllowed')) {
            $menu->setExtra('isAllowed', $isAllowed);
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
