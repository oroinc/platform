<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Builder;

use Knp\Menu\ItemInterface;

use Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProviderInterface;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;

class MenuUpdateBuilderTest extends \PHPUnit_Framework_TestCase
{
    use MenuItemTestTrait;

    /** @var MenuUpdateBuilder */
    protected $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->builder = new MenuUpdateBuilder();
        $this->prepareMenu();
    }

    /**
     * @dataProvider menuUpdateProvider
     *
     * @param $expectedData
     * @param $updateData
     */
    public function testBuild($expectedData, $updateData)
    {
        $menuUpdate = $this->getMenuUpdate($updateData);

        /** @var MenuUpdateProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(MenuUpdateProviderInterface::class);
        $provider->expects($this->once())
            ->method('getUpdates')
            ->will($this->returnValue([$menuUpdate]));

        $this->builder->addProvider('default', $provider);

        $this->menu->setExtra('area', 'default');

        if (!$expectedData['predefined']) {
            $this->assertNull($this->menu->getChild($menuUpdate->getKey()));
        }

        /** @var ItemInterface $parentMenu */
        $parentMenu = $this->{$updateData['parent']};
        if (!$expectedData['predefined']) {
            $this->assertNull($parentMenu->getChild($menuUpdate->getKey()));
        }

        $this->builder->build($this->menu);

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
     * @expectedException \Oro\Bundle\NavigationBundle\Exception\ProviderNotFoundException
     * @expectedExceptionMessage Provider related to "custom" area not found.
     */
    public function testBuildProviderNotFoundException()
    {
        /** @var MenuUpdateProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(MenuUpdateProviderInterface::class);

        $this->builder->addProvider('default', $provider);

        $this->menu->setExtra('area', 'custom');

        $this->builder->build($this->menu);
    }

    /**
     * @return array
     */
    public function menuUpdateProvider()
    {
        return [
            // update menu item using existing key without changing parent
            [
                [
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
                [
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
            // create new menu item and add to Root Menu
            [
                [
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
                [
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
            // change parent from Parent 1 to Parent 2
            [
                [
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
                [
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
        $update->setTitle($updateData['title']);
        $update->setKey($updateData['key']);
        $update->setParentKey($updateData['parent_key']);
        $update->setMenu($updateData['menu_key']);
        $update->setUri($updateData['uri']);
        $update->setActive($updateData['active']);
        $update->setExtras($updateData['extras']);

        return $update;
    }
}
