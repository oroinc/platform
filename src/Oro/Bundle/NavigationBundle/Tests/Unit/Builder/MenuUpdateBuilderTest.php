<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Builder;

use Knp\Menu\ItemInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProviderInterface;

class MenuUpdateBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MenuUpdateBuilder */
    private $menuUpdateBuilder;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var ItemInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $menuItem;

    /** @var MenuUpdateProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $menuUpdateProvider;

    protected function setUp()
    {
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->menuItem = $this->createMock(ItemInterface::class);

        $this->menuUpdateProvider = $this->createMock(MenuUpdateProviderInterface::class);

        $this->menuUpdateBuilder = new MenuUpdateBuilder(
            $this->localizationHelper,
            $this->menuUpdateProvider
        );
    }

    /**
     * @expectedException Oro\Bundle\NavigationBundle\Exception\MaxNestingLevelExceededException
     * @expectedExceptionMessage Item "ChildMenuItem" exceeded max nesting level in menu "MainMenuItem".
     */
    public function testMaxNestingLevelExceededException()
    {
        $this->menuUpdateProvider->expects(static::once())
            ->method('getMenuUpdatesForMenuItem')
            ->with($this->menuItem)
            ->willReturn([]);

        /** @var ItemInterface|\PHPUnit\Framework\MockObject\MockObject */
        $childItem = $this->createMock(ItemInterface::class);

        $this->menuItem->expects(static::exactly(2))
            ->method('getExtra')
            ->withConsecutive(
                ['divider', false],
                ['max_nesting_level', 0]
            )
            ->willReturnOnConsecutiveCalls(false, 10);

        $childItem->expects($this->once())
            ->method('getLevel')
            ->willReturn(11);

        $childItem->expects($this->exactly(1))
            ->method('getChildren')
            ->willReturn([]);

        $childItem->expects($this->once())
            ->method('getLabel')
            ->willReturn('ChildMenuItem');

        $this->menuItem->expects($this->exactly(2))
            ->method('getChildren')
            ->willReturnOnConsecutiveCalls([$childItem], [$childItem]);

        $this->menuItem->expects($this->once())
            ->method('getLabel')
            ->willReturn('MainMenuItem');

        $this->menuUpdateBuilder->build($this->menuItem);
    }

    public function testBuild()
    {
        $this->menuUpdateProvider->expects(static::once())
            ->method('getMenuUpdatesForMenuItem')
            ->with($this->menuItem)
            ->willReturn([]);

        $this->menuItem->expects($this->exactly(2))
            ->method('getChildren')
            ->willReturn([]);

        $this->menuUpdateBuilder->build($this->menuItem);
    }
}
