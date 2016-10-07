<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Utils;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

class MenuUpdateUtilsTest extends \PHPUnit_Framework_TestCase
{
    use MenuItemTestTrait;
    
    public function testUpdateMenuUpdate()
    {
        $menu = $this->getMenu();
        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $update = new MenuUpdateStub();
        $update->setKey('item-1-1-1');

        $expectedUpdate = new MenuUpdateStub();
        $expectedUpdate->setKey('item-1-1-1');
        $expectedUpdate->setParentKey('item-1-1');
        $expectedUpdate->setMenu('menu');
        $expectedUpdate->setDefaultTitle('item-1-1-1');
        $expectedUpdate->setExistsInNavigationYml(true);

        MenuUpdateUtils::updateMenuUpdate($update, $item, 'menu');
        $this->assertEquals($expectedUpdate, $update);
    }
    
    public function testUpdateMenuItem()
    {
        $menu = $this->getMenu();
        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $update = new MenuUpdateStub();
        $update->setKey('item-1-1-1');
        $update->setParentKey('item-2');
        $update->setUri('URI');

        $expectedItem = $this->createItem('item-1-1-1');
        $expectedItem->setParent($menu->getChild('item-2'));
        $expectedItem->setUri('URI');
        $expectedItem->setExtra('editable', true);

        /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject $localizationHelper */
        $localizationHelper = $this->getMock(LocalizationHelper::class, [], [], '', false);
        MenuUpdateUtils::updateMenuItem($update, $menu, $localizationHelper);

        $this->assertEquals($expectedItem, $item);
    }

    public function testUpdateMenuItemWithoutItem()
    {
        $menu = $this->getMenu();

        $update = new MenuUpdateStub();
        $update->setKey('item-1-1-1-1');
        $update->setParentKey('item-2');
        $update->setUri('URI');

        $expectedItem = $this->createItem('item-1-1-1-1');
        $expectedItem->setParent($menu->getChild('item-2'));
        $expectedItem->setUri('URI');
        $expectedItem->setExtra('userDefined', true);
        $expectedItem->setExtra('editable', true);

        /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject $localizationHelper */
        $localizationHelper = $this->getMock(LocalizationHelper::class, [], [], '', false);
        MenuUpdateUtils::updateMenuItem($update, $menu, $localizationHelper);

        $this->assertEquals($expectedItem, $menu->getChild('item-2')->getChild('item-1-1-1-1'));
    }
    
    public function testFindMenuItem()
    {
        $menu = $this->getMenu();

        $expectedItem = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $this->assertEquals($expectedItem, MenuUpdateUtils::findMenuItem($menu, 'item-1-1-1'));
    }

    public function testFindMenuItemNull()
    {
        $menu = $this->getMenu();

        $this->assertEquals(null, MenuUpdateUtils::findMenuItem($menu, 'item-1-1-1-1'));
    }
    
    public function testGetItemExceededMaxNestingLevel()
    {
        $menu = $this->getMenu();
        $menu->setExtra('max_nesting_level', 2);

        $item = $menu->getChild('item-1');

        $expectedItem = $item->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $this->assertEquals($expectedItem, MenuUpdateUtils::getItemExceededMaxNestingLevel($menu, $item));
    }

    public function testGetItemExceededMaxNestingLevelNull()
    {
        $menu = $this->getMenu();
        $menu->setExtra('max_nesting_level', 3);

        $item = $menu->getChild('item-1');

        $this->assertEquals(null, MenuUpdateUtils::getItemExceededMaxNestingLevel($menu, $item));
    }
}
