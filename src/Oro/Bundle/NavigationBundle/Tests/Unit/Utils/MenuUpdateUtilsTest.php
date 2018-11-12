<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Utils;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Menu\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTrait;

class MenuUpdateUtilsTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use MenuItemTestTrait;

    /** @var MenuUpdateHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $menuUpdateHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->menuUpdateHelper = $this->createMock(MenuUpdateHelper::class);
    }

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

        $this->menuUpdateHelper
            ->expects($this->once())
            ->method('applyLocalizedFallbackValue')
            ->with($update, 'item-1-1-1', 'title', 'string');

        MenuUpdateUtils::updateMenuUpdate($update, $item, 'menu', $this->menuUpdateHelper);
        $this->assertEquals($expectedUpdate, $update);
    }
    
    public function testUpdateMenuItem()
    {
        $menu = $this->getMenu();
        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $firstLocalization = new Localization();
        $this->setValue($firstLocalization, 'id', 1);
        $secondLocalization = new Localization();
        $this->setValue($secondLocalization, 'id', 2);

        $update = new MenuUpdateStub();
        $update->setKey('item-1-1-1');
        $update->setParentKey('item-2');
        $update->setUri('URI');
        $update->addDescription(
            (new LocalizedFallbackValue())
                ->setString('first test description')
                ->setLocalization($firstLocalization)
        );
        $update->addDescription(
            (new LocalizedFallbackValue())
                ->setString('second test description')
                ->setLocalization($secondLocalization)
        );

        $expectedItem = $this->createItem('item-1-1-1');
        $expectedItem->setParent($menu->getChild('item-2'));
        $expectedItem->setUri('URI');
        $expectedItem->setExtra('description', 'second test description');

        /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject $localizationHelper */
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $localizationHelper->expects($this->atLeastOnce())
            ->method('getCurrentLocalization')
            ->willReturn($secondLocalization);

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

        /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject $localizationHelper */
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        MenuUpdateUtils::updateMenuItem($update, $menu, $localizationHelper);

        $this->assertNull($menu->getChild('item-2')->getChild('item-1-1-1-1'));
    }

    public function testUpdateMenuItemWithoutItemIsCustom()
    {
        $menu = $this->getMenu();

        $update = new MenuUpdateStub();
        $update->setKey('item-1-1-1-1');
        $update->setParentKey('item-2');
        $update->setUri('URI');
        $update->setCustom(true);

        $expectedItem = $this->createItem('item-1-1-1-1');
        $expectedItem->setParent($menu->getChild('item-2'));
        $expectedItem->setUri('URI');
        $update->setCustom(true);

        /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject $localizationHelper */
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        MenuUpdateUtils::updateMenuItem($update, $menu, $localizationHelper);

        $this->assertEquals($expectedItem, $menu->getChild('item-2')->getChild('item-1-1-1-1'));
    }

    public function testUpdateMenuItemWithOptions()
    {
        $menu = $this->getMenu();
        $item = $menu->getChild('item-1');

        $update = new MenuUpdateStub();
        $update->setKey('new');
        $update->setParentKey('item-1');
        $update->setCustom(true);

        /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject $localizationHelper */
        $localizationHelper = $this->createMock(LocalizationHelper::class);

        $options = ['extras' => ['test' => 'test']];
        MenuUpdateUtils::updateMenuItem($update, $menu, $localizationHelper, $options);

        $this->assertEquals(['test' => 'test'], $item->getChild('new')->getExtras());
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

    public function testGenerateKey()
    {
        $menuName = 'application_menu';
        $scope = $this->getEntity(Scope::class, ['id' => 1]);

        $this->assertEquals('application_menu_1', MenuUpdateUtils::generateKey($menuName, $scope));
    }
}
