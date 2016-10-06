<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Builder;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProviderInterface;
use Oro\Bundle\NavigationBundle\Menu\Provider\OwnershipProviderInterface;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;

class MenuUpdateBuilderTest extends \PHPUnit_Framework_TestCase
{
    use MenuItemTestTrait;

    /** @var MenuUpdateBuilder */
    protected $builder;

    /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->localizationHelper = $this->getMock(LocalizationHelper::class, [], [], '', false);

        $this->builder = new MenuUpdateBuilder($this->localizationHelper);
    }

    public function testBuild()
    {
        $menuUpdate = new MenuUpdateStub();
        $menuUpdate->setMenu('menu');
        $menuUpdate->setKey('item-1-1-1-1');
        $menuUpdate->setParentKey('item-1-1-1');

        /** @var OwnershipProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(OwnershipProviderInterface::class);
        $provider->expects($this->once())
            ->method('getMenuUpdates')
            ->will($this->returnValue([$menuUpdate]));

        $this->builder->addProvider($provider, 'default', 100);

        $menu = $this->getMenu();
        $menu->setExtra('area', 'default');

        $this->builder->build($menu);

        $result = $this->getMenu();
        $result->setExtra('area', 'default');
        $child = $result->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

         $child->addChild('item-1-1-1-1')
            ->setExtra('userDefined', true)
            ->setExtra('editable', true);
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

        /** @var MenuUpdateProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(MenuUpdateProviderInterface::class);
        $provider->expects($this->once())
            ->method('getUpdates')
            ->will($this->returnValue([$menuUpdate]));

        $this->builder->addProvider('default', $provider);

        $menu = $this->getMenu();
        $menu->setExtra('area', 'default');
        $menu->setExtra('max_nesting_level', 3);

        $this->builder->build($menu);
    }
}
