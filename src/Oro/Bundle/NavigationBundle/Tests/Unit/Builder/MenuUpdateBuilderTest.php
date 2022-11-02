<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Builder;

use Knp\Menu\ItemInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\Exception\MaxNestingLevelExceededException;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProviderInterface;

class MenuUpdateBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var MenuUpdateProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $menuUpdateProvider;

    /** @var ItemInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $menuItem;

    /** @var MenuUpdateBuilder */
    private $menuUpdateBuilder;

    protected function setUp(): void
    {
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->menuUpdateProvider = $this->createMock(MenuUpdateProviderInterface::class);
        $this->menuItem = $this->createMock(ItemInterface::class);

        $this->menuUpdateBuilder = new MenuUpdateBuilder(
            $this->localizationHelper,
            $this->menuUpdateProvider
        );
    }

    public function testMaxNestingLevelExceededException()
    {
        $this->expectException(MaxNestingLevelExceededException::class);
        $this->expectExceptionMessage('Item "ChildMenuItem" exceeded max nesting level in menu "MainMenuItem".');

        $this->menuUpdateProvider->expects(self::once())
            ->method('getMenuUpdatesForMenuItem')
            ->with($this->menuItem)
            ->willReturn([]);

        $childItem = $this->createMock(ItemInterface::class);

        $this->menuItem->expects(self::exactly(2))
            ->method('getExtra')
            ->withConsecutive(
                ['divider', false],
                ['max_nesting_level', 0]
            )
            ->willReturnOnConsecutiveCalls(false, 10);

        $childItem->expects($this->once())
            ->method('getLevel')
            ->willReturn(11);

        $childItem->expects($this->once())
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
        $this->menuUpdateProvider->expects(self::once())
            ->method('getMenuUpdatesForMenuItem')
            ->with($this->menuItem)
            ->willReturn([]);

        $this->menuItem->expects($this->exactly(2))
            ->method('getChildren')
            ->willReturn([]);

        $this->menuUpdateBuilder->build($this->menuItem);
    }
}
