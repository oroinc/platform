<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\MenuUpdate\Propagator\ToMenuUpdate;

use Doctrine\Common\Collections\ArrayCollection;
use Knp\Menu\ItemInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Menu\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\MenuItemToMenuUpdatePropagatorInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\TitlePropagator;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class TitlePropagatorTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private MenuUpdateHelper|\PHPUnit\Framework\MockObject\MockObject $menuUpdateHelper;

    private TitlePropagator $propagator;

    protected function setUp(): void
    {
        $this->menuUpdateHelper = $this->createMock(MenuUpdateHelper::class);

        $this->propagator = new TitlePropagator(new PropertyAccessor(), $this->menuUpdateHelper);
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

    public function testPropagateFromMenuItemWhenTitleAlreadySet(): void
    {
        $menuUpdate = (new MenuUpdateStub())
            ->setKey('sample_item');
        $menuUpdate->setDefaultTitle('sample title');

        $menu = $this->createItem('sample_menu');
        $menuItem = $menu->addChild(
            $menuUpdate->getKey(),
            [
                'extras' => [
                    'titles' => new ArrayCollection([(new LocalizedFallbackValue())->setString('menu item title')])
                ]
            ]
        );

        $this->menuUpdateHelper
            ->expects(self::never())
            ->method(self::anything());

        $expected = new ArrayCollection([(new LocalizedFallbackValue())->setString('sample title')]);
        self::assertEquals($expected, $menuUpdate->getTitles());

        $this->propagator->propagateFromMenuItem(
            $menuUpdate,
            $menuItem,
            MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL
        );

        self::assertEquals($expected, $menuUpdate->getTitles());
    }

    public function testPropagateFromMenuItemWithTitles(): void
    {
        $menuUpdate = (new MenuUpdateStub())
            ->setKey('sample_item');

        $menu = $this->createItem('sample_menu');
        $menuItem = $menu->addChild(
            $menuUpdate->getKey(),
            [
                'extras' => [
                    'titles' => new ArrayCollection([(new LocalizedFallbackValue())->setString('menu item title')])
                ]
            ]
        );

        $this->menuUpdateHelper
            ->expects(self::never())
            ->method('applyLocalizedFallbackValue');

        self::assertEquals(new ArrayCollection(), $menuUpdate->getTitles());

        $this->propagator->propagateFromMenuItem(
            $menuUpdate,
            $menuItem,
            MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL
        );

        self::assertEquals($menuItem->getExtra('titles'), $menuUpdate->getTitles());
    }

    public function testPropagateFromMenuItemWithEmptyTitles(): void
    {
        $menuUpdate = (new MenuUpdateStub())
            ->setKey('sample_item');

        $menu = $this->createItem('sample_menu');
        $menuItem = $menu->addChild(
            $menuUpdate->getKey(),
            [
                'label' => 'menu item label',
                'extras' => [
                    'titles' => new ArrayCollection()
                ],
            ]
        );

        $this->menuUpdateHelper
            ->expects(self::once())
            ->method('applyLocalizedFallbackValue')
            ->with($menuUpdate, $menuItem->getLabel(), 'title', 'string')
            ->willReturnCallback(function (MenuUpdateInterface $menuUpdate, $description) {
                $menuUpdate->setDefaultTitle($description);
            });

        self::assertEquals(new ArrayCollection(), $menuUpdate->getTitles());

        $this->propagator->propagateFromMenuItem(
            $menuUpdate,
            $menuItem,
            MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL
        );

        self::assertEquals(
            new ArrayCollection([(new LocalizedFallbackValue())->setString('menu item label')]),
            $menuUpdate->getTitles()
        );
    }

    public function testPropagateFromMenuItemWithoutTitlesAndTranslateDisabled(): void
    {
        $menuUpdate = (new MenuUpdateStub())
            ->setKey('sample_item');

        $menu = $this->createItem('sample_menu');
        $menuItem = $menu->addChild(
            $menuUpdate->getKey(),
            [
                'label' => 'menu item label',
                'extras' => [
                    'translate_disabled' => true,
                ],
            ]
        );

        $this->menuUpdateHelper
            ->expects(self::never())
            ->method('applyLocalizedFallbackValue');

        self::assertEquals(new ArrayCollection(), $menuUpdate->getTitles());

        $this->propagator->propagateFromMenuItem(
            $menuUpdate,
            $menuItem,
            MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL
        );

        self::assertEquals(
            new ArrayCollection([(new LocalizedFallbackValue())->setString('menu item label')]),
            $menuUpdate->getTitles()
        );
    }
}
