<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Helper;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Exception\InvalidMaxNestingLevelException;
use Oro\Bundle\NavigationBundle\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;

class MenuUpdateHelperTest extends \PHPUnit_Framework_TestCase
{
    use MenuItemTestTrait;
    
    /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationHelper;

    /** @var MenuUpdateHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->localizationHelper = $this->getMock(LocalizationHelper::class, [], [], '', false);

        $this->helper = new MenuUpdateHelper($this->localizationHelper);

        $this->prepareMenu();
    }

    /**
     * @dataProvider menuUpdateProvider
     *
     * @param $menuName
     * @param array $itemData
     * @param array $extraMapping
     * @param array $result
     */
    public function testUpdateMenuUpdate($menuName, array $itemData, array $extraMapping, array $result)
    {
        $update = new MenuUpdateStub();

        $factory = new MenuFactory();
        $item = $factory->createItem($itemData['name'], $itemData);
        if ($itemData['parent']) {
            $item->setParent($itemData['parent']);
        }
        
        $this->helper->updateMenuUpdate($update, $item, $menuName, $extraMapping);
        
        foreach ($result as $key => $value) {
            $this->assertEquals($value, $update->{$key}());
        }
    }

    /**
     * @dataProvider menuItemProvider
     *
     * @param $expectedData
     * @param $updateData
     */
    public function testUpdateMenuItem($expectedData, $updateData)
    {
        $menuUpdate = $this->getMenuUpdate($updateData);

        if (!$expectedData['predefined']) {
            $this->assertNull($this->menu->getChild($menuUpdate->getKey()));
        }

        /** @var ItemInterface $parentMenu */
        $parentMenu = $this->{$updateData['parent']};
        if (!$expectedData['predefined']) {
            $this->assertNull($parentMenu->getChild($menuUpdate->getKey()));
        }

        $this->localizationHelper
            ->expects($this->once())
            ->method('getLocalizedValue')
            ->with($menuUpdate->getTitles())
            ->will($this->returnValue($menuUpdate->getTitles()->first()));

        $this->helper->updateMenuItem($menuUpdate, $this->menu);

        $parentMenu = $this->{$expectedData['parent']};
        if (!$expectedData['predefined']) {
            $this->assertNotNull($parentMenu->getChild($menuUpdate->getKey()));
        }

        $childMenu = $parentMenu->getChild($menuUpdate->getKey());

        $this->assertEquals($expectedData['parent_name'], $childMenu->getParent()->getName());

        $this->assertEquals($expectedData['label'], $childMenu->getLabel());
        $this->assertEquals($expectedData['uri'], $childMenu->getUri());
        $this->assertEquals($expectedData['display'], $childMenu->isDisplayed());

        foreach ($expectedData['extras'] as $extraKey => $extraValue) {
            $this->assertEquals($extraValue, $childMenu->getExtra($extraKey));
        }
    }

    /**
     * @dataProvider maxNestingLevelProvider
     *
     * @param int $level
     * @param int $maxLevel
     * @param bool $isGreaterThan
     * @param bool $result
     */
    public function testIsMaxNestingLevelReached($level, $maxLevel, $isGreaterThan, $result)
    {
        /** @var ItemInterface|\PHPUnit_Framework_MockObject_MockObject $menu */
        $menu = $this->getMock(ItemInterface::class);
        $menu->expects($this->once())
            ->method('getExtra')
            ->with('max_nesting_level', 0)
            ->will($this->returnValue($maxLevel));

        $factory = new MenuFactory();
        $item = $factory->createItem(0);
        $startItem = $item;

        $range = range(0, $level);
        array_shift($range);
        foreach ($range as $value) {
            $item->setParent($factory->createItem($value));
            $item = $item->getParent();
        }

        $this->assertEquals($result, $this->helper->isMaxNestingLevelReached($menu, $startItem, $isGreaterThan));
    }

    /**
     * @return array
     */
    public function menuUpdateProvider()
    {
        $factory = new MenuFactory();
        $parent = $factory->createItem('Parent 4');

        return [
            'update' => [
                'menu' => 'Menu 1',
                'item' => [
                    'name' => 'Item 1',
                    'uri' => 'Uri 1',
                    'label' => 'Label 1',
                    'parent' => null,
                    'display' => true,
                    'extras' => [],
                ],
                'extra_mapping' => [],
                'result' => [
                    'getKey' => 'Item 1',
                    'getParentKey' => null,
                    'getUri' => 'Uri 1',
                    'getDefaultTitle' => 'Label 1',
                    'isActive' => true,
                    'getMenu' => 'Menu 1',
                    'getPriority' => null,
                ],
            ],
            'update_with_extras' => [
                'menu' => 'Menu 2',
                'item' => [
                    'name' => 'Item 2',
                    'uri' => 'Uri 2',
                    'label' => 'Label 2',
                    'parent' => null,
                    'display' => false,
                    'extras' => [
                        'priority' => 'Priority 2'
                    ],
                ],
                'extra_mapping' => [],
                'result' => [
                    'getKey' => 'Item 2',
                    'getParentKey' => null,
                    'getUri' => 'Uri 2',
                    'getDefaultTitle' => 'Label 2',
                    'isActive' => false,
                    'getMenu' => 'Menu 2',
                    'getPriority' => 'Priority 2',
                ],
            ],
            'update_with_extras_mapping' => [
                'menu' => 'Menu 3',
                'item' => [
                    'name' => 'Item 3',
                    'uri' => 'Uri 3',
                    'label' => 'Label 3',
                    'parent' => null,
                    'display' => true,
                    'extras' => [
                        'position' => 'Priority 3'
                    ],
                ],
                'extra_mapping' => ['position' => 'priority'],
                'result' => [
                    'getKey' => 'Item 3',
                    'getParentKey' => null,
                    'getUri' => 'Uri 3',
                    'getDefaultTitle' => 'Label 3',
                    'isActive' => true,
                    'getMenu' => 'Menu 3',
                    'getPriority' => 'Priority 3',
                ],
            ],
            'update_with_parent' => [
                'menu' => 'Menu 4',
                'item' => [
                    'name' => 'Item 4',
                    'uri' => 'Uri 4',
                    'label' => 'Label 4',
                    'parent' => $parent,
                    'display' => false,
                    'extras' => [],
                ],
                'extra_mapping' => [],
                'result' => [
                    'getKey' => 'Item 4',
                    'getParentKey' => 'Parent 4',
                    'getUri' => 'Uri 4',
                    'getDefaultTitle' => 'Label 4',
                    'isActive' => false,
                    'getMenu' => 'Menu 4',
                    'getPriority' => null,
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    public function menuItemProvider()
    {
        return [
            'update_existing_item_without_move' => [
                'result_data' => [
                    'parent' => 'menu',
                    'predefined' => true,
                    'label' => 'Title 1',
                    'parent_name' => 'Root Menu',
                    'uri' => 'Uri 1',
                    'display' => true,
                    'extras' => [
                        'extra_1' => 'Extra 1'
                    ]
                ],
                'update_data' => [
                    'parent' => 'menu',
                    'title' => 'Title 1',
                    'key' => 'Parent 1',
                    'parent_key' => null,
                    'menu_key'  => 'Root Menu',
                    'uri' => 'Uri 1',
                    'active' => true,
                    'extras' => [
                        'extra_1' => 'Extra 1'
                    ]
                ]
            ],
            'create_new_item' => [
                'result_data' => [
                    'parent' => 'menu',
                    'predefined' => false,
                    'label' => 'Title 2',
                    'parent_name' => 'Root Menu',
                    'uri' => 'Uri 2',
                    'display' => false,
                    'extras' => [
                        'extra_2' => 'Extra 2'
                    ]
                ],
                'update_data' => [
                    'parent' => 'menu',
                    'title' => 'Title 2',
                    'key' => 'Parent N',
                    'parent_key' => null,
                    'menu_key'  => 'Root Menu',
                    'uri' => 'Uri 2',
                    'active' => false,
                    'extras' => [
                        'extra_2' => 'Extra 2'
                    ]
                ]
            ],
            'update_existing_item_with_move' => [
                'result_data' => [
                    'parent' => 'pt2',
                    'predefined' => true,
                    'label' => 'Title 3',
                    'parent_name' => 'Parent 2',
                    'uri' => 'Uri 3',
                    'display' => true,
                    'extras' => [
                        'extra_3' => 'Extra 3'
                    ]
                ],
                'update_data' => [
                    'parent' => 'pt1',
                    'title' => 'Title 3',
                    'key' => 'Child 1',
                    'parent_key' => 'Parent 2',
                    'menu_key'  => 'Root Menu',
                    'uri' => 'Uri 3',
                    'active' => true,
                    'extras' => [
                        'extra_3' => 'Extra 3'
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $updateData
     *
     * @return MenuUpdateStub
     */
    protected function getMenuUpdate(array $updateData)
    {
        $update = new MenuUpdateStub();

        $title = new LocalizedFallbackValue();
        $title->setString($updateData['title']);

        $update->addTitle($title);
        $update->setKey($updateData['key']);
        $update->setParentKey($updateData['parent_key']);
        $update->setMenu($updateData['menu_key']);
        $update->setUri($updateData['uri']);
        $update->setActive($updateData['active']);
        $update->setExtras($updateData['extras']);

        return $update;
    }

    /**
     * @return array
     */
    public function maxNestingLevelProvider()
    {
        return [
            [1, 0, false, false],
            [2, 0, false, false],
            [3, 0, false, false],
            [1, 1, false, true],
            [2, 1, false, true],
            [3, 1, false, true],
            [1, 2, false, false],
            [2, 2, false, true],
            [3, 2, false, true],
            [1, 3, false, false],
            [2, 3, false, false],
            [3, 3, false, true],
            // With greater than param
            [1, 0, true, false],
            [2, 0, true, false],
            [3, 0, true, false],
            [1, 1, true, false],
            [2, 1, true, true],
            [3, 1, true, true],
            [1, 2, true, false],
            [2, 2, true, false],
            [3, 2, true, true],
            [1, 3, true, false],
            [2, 3, true, false],
            [3, 3, true, false],
        ];
    }
}
