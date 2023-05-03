<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\MenuUpdate\Propagator\ToMenuUpdate;

use Doctrine\Common\Collections\ArrayCollection;
use Knp\Menu\ItemInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Menu\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\DescriptionPropagator;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\MenuItemToMenuUpdatePropagatorInterface;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;

class DescriptionPropagatorTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private MenuUpdateHelper|\PHPUnit\Framework\MockObject\MockObject $menuUpdateHelper;

    private DescriptionPropagator $propagator;

    protected function setUp(): void
    {
        $this->menuUpdateHelper = $this->createMock(MenuUpdateHelper::class);

        $this->propagator = new DescriptionPropagator($this->menuUpdateHelper);
    }

    /**
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(string $strategy, bool $expected): void
    {
        $menuUpdate = new MenuUpdateStub();
        $menuItem = $this->createMock(ItemInterface::class);

        self::assertSame(
            $expected,
            $this->propagator->isApplicable($menuUpdate, $menuItem, $strategy)
        );
    }

    public function isApplicableDataProvider(): array
    {
        return [
            'none' => [
                'strategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_NONE,
                'expected' => false,
            ],
            'basic' => [
                'strategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC,
                'expected' => false,
            ],
            'full' => [
                'strategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL,
                'expected' => true,
            ],
        ];
    }

    public function testPropagateFromMenuItemWhenDescriptionAlreadySet(): void
    {
        $menuUpdate = (new MenuUpdateStub())
            ->setKey('sample_item');
        $menuUpdate->setDefaultDescription('sample description');

        $menu = $this->createItem('sample_menu');
        $menuItem = $menu->addChild($menuUpdate->getKey(), ['extras' => ['description' => 'menu item description']]);

        $this->menuUpdateHelper
            ->expects(self::never())
            ->method(self::anything());

        $expected = new ArrayCollection([(new LocalizedFallbackValue())->setText('sample description')]);
        self::assertEquals($expected, $menuUpdate->getDescriptions());

        $this->propagator->propagateFromMenuItem(
            $menuUpdate,
            $menuItem,
            MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL
        );

        self::assertEquals($expected, $menuUpdate->getDescriptions());
    }

    public function testPropagateFromMenuItemWhenDescriptionNotSet(): void
    {
        $menuUpdate = (new MenuUpdateStub())
            ->setKey('sample_item');

        $menu = $this->createItem('sample_menu');
        $menuItem = $menu->addChild($menuUpdate->getKey(), ['extras' => ['description' => 'menu item description']]);

        $this->menuUpdateHelper
            ->expects(self::once())
            ->method('applyLocalizedFallbackValue')
            ->with($menuUpdate, $menuItem->getExtra('description'), 'description', 'text')
            ->willReturnCallback(function (MenuUpdateInterface $menuUpdate, $description) {
                $menuUpdate->setDefaultDescription($description);
            });

        self::assertEquals(new ArrayCollection(), $menuUpdate->getDescriptions());

        $this->propagator->propagateFromMenuItem(
            $menuUpdate,
            $menuItem,
            MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL
        );

        self::assertEquals(
            new ArrayCollection([(new LocalizedFallbackValue())->setText($menuItem->getExtra('description'))]),
            $menuUpdate->getDescriptions()
        );
    }
}
