<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Builder;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManagerRegistry;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

class MenuUpdateBuilderTest extends \PHPUnit_Framework_TestCase
{
    use MenuItemTestTrait;

    /** @var MenuUpdateBuilder */
    protected $builder;

    /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationHelper;

    /** @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $scopeManager;

    /** @var MenuUpdateManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->localizationHelper = $this
            ->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeManager = $this
            ->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this
            ->getMockBuilder(MenuUpdateManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new MenuUpdateBuilder($this->localizationHelper, $this->scopeManager, $this->registry);
    }

    public function testBuild()
    {
        $menuUpdate = new MenuUpdateStub();
        $menuUpdate->setMenu('menu');
        $menuUpdate->setKey('item-1-1-1-1');
        $menuUpdate->setParentKey('item-1-1-1');
        $menuUpdate->setCustom(true);

        $menu = $this->getMenu();
        $menu->setExtra('area', 'menu_default_visibility');

        $this->scopeManager
            ->expects($this->once())
            ->method('findRelatedScopeIdsWithPriority')
            ->with('menu_default_visibility', null)
            ->willReturn([1, 2]);

        $manager = $this->getMockBuilder(MenuUpdateManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())
            ->method('getMenuUpdatesByScopeIds')
            ->with($menu->getName(), [1, 2])
            ->willReturn([$menuUpdate]);

        $this->registry
            ->expects($this->once())
            ->method('getManager')
            ->with('menu_default_visibility')
            ->willReturn($manager);

        $this->builder->build($menu);

        $result = $this->getMenu();
        $result->setExtra('area', 'menu_default_visibility');
        $child = $result->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $child->addChild('item-1-1-1-1');
        
        $this->assertEquals($result, $menu);
    }

    public function testBuildWithNotCustomUpdate()
    {
        $menuUpdate = new MenuUpdateStub();
        $menuUpdate->setMenu('menu');
        $menuUpdate->setKey('item-1-1-1-1');
        $menuUpdate->setParentKey('item-1-1-1');

        $menu = $this->getMenu();
        $menu->setExtra('area', 'menu_default_visibility');

        $this->scopeManager
            ->expects($this->once())
            ->method('findRelatedScopeIdsWithPriority')
            ->with('menu_default_visibility', null)
            ->willReturn([1, 2]);

        $manager = $this->getMockBuilder(MenuUpdateManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())
            ->method('getMenuUpdatesByScopeIds')
            ->with($menu->getName(), [1, 2])
            ->willReturn([$menuUpdate]);

        $this->registry
            ->expects($this->once())
            ->method('getManager')
            ->with('menu_default_visibility')
            ->willReturn($manager);

        $this->builder->build($menu);

        $result = $this->getMenu();
        $result->setExtra('area', 'menu_default_visibility');

        $this->assertEquals($result, $menu);
    }

    /**
     * @expectedException \Oro\Bundle\NavigationBundle\Exception\MaxNestingLevelExceededException
     * @expectedExceptionMessage Item "item-1-1-1-1" exceeded max nesting level in menu "menu".
     */
    public function testBuildMaxNestingLevelExceededException()
    {
        $menuUpdate = new MenuUpdateStub();
        $menuUpdate->setMenu('menu');
        $menuUpdate->setKey('item-1-1-1-1');
        $menuUpdate->setParentKey('item-1-1-1');
        $menuUpdate->setCustom(true);

        $menu = $this->getMenu();
        $menu->setExtra('area', 'menu_default_visibility');
        $menu->setExtra('max_nesting_level', 3);

        $this->scopeManager
            ->expects($this->once())
            ->method('findRelatedScopeIdsWithPriority')
            ->with('menu_default_visibility', null)
            ->willReturn([1, 2]);

        $manager = $this->getMockBuilder(MenuUpdateManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->once())
            ->method('getMenuUpdatesByScopeIds')
            ->with($menu->getName(), [1, 2])
            ->willReturn([$menuUpdate]);

        $this->registry
            ->expects($this->once())
            ->method('getManager')
            ->with('menu_default_visibility')
            ->willReturn($manager);

        $this->builder->build($menu);
    }
}
