<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\MenuUpdate\Propagator\ToMenuItem;

use Knp\Menu\ItemInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem\BasicPropagator;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem\MenuUpdateToMenuItemPropagatorInterface;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;

class BasicPropagatorTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private BasicPropagator $propagator;

    protected function setUp(): void
    {
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $localizationHelper
            ->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static fn ($collection) => $collection[0] ?? null);

        $this->propagator = new BasicPropagator($localizationHelper);
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
            $this->propagator->isApplicable($menuItem, $menuUpdate, $strategy)
        );
    }

    public function isApplicableDataProvider(): array
    {
        return [
            'none' => [
                'strategy' => MenuUpdateToMenuItemPropagatorInterface::STRATEGY_NONE,
                'expected' => false,
            ],
            'basic' => [
                'strategy' => MenuUpdateToMenuItemPropagatorInterface::STRATEGY_BASIC,
                'expected' => true,
            ],
            'full' => [
                'strategy' => MenuUpdateToMenuItemPropagatorInterface::STRATEGY_FULL,
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider propagateFromMenuUpdateDataProvider
     */
    public function testPropagateFromMenuUpdate(
        MenuUpdateInterface $menuUpdate,
        ItemInterface $expected
    ): void {
        $menuItem = $this->createItem('sample_item');

        $this->propagator->propagateFromMenuUpdate(
            $menuItem,
            $menuUpdate,
            MenuUpdateToMenuItemPropagatorInterface::STRATEGY_BASIC
        );

        self::assertEquals($expected, $menuItem);
    }

    public function propagateFromMenuUpdateDataProvider(): array
    {
        return [
            'empty' => [
                'menuUpdate' => new MenuUpdate(),
                'expected' => $this->createItem('sample_item'),
            ],
            'with titles' => [
                'menuUpdate' => (new MenuUpdate())->addTitle((new LocalizedFallbackValue())->setString('sample title')),
                'expected' => $this->createItem('sample_item')->setLabel('sample title'),
            ],
            'with uri' => [
                'menuUpdate' => (new MenuUpdate())
                    ->addTitle((new LocalizedFallbackValue())->setString('sample title'))
                    ->setUri('/sample/uri'),
                'expected' => $this->createItem('sample_item')
                    ->setLabel('sample title')
                    ->setUri('/sample/uri'),
            ],
            'with active true' => [
                'menuUpdate' => (new MenuUpdate())
                    ->addTitle((new LocalizedFallbackValue())->setString('sample title'))
                    ->setUri('/sample/uri')
                    ->setActive(true),
                'expected' => $this->createItem('sample_item')
                    ->setLabel('sample title')
                    ->setUri('/sample/uri')
                    ->setDisplay(true),
            ],
            'with link attributes' => [
                'menuUpdate' => (new MenuUpdateStub())
                    ->addTitle((new LocalizedFallbackValue())->setString('sample title'))
                    ->setUri('/sample/uri')
                    ->setActive(false)
                    ->setLinkAttributes(['sample_key' => 'sample_value']),
                'expected' => $this->createItem('sample_item')
                    ->setLabel('sample title')
                    ->setUri('/sample/uri')
                    ->setDisplay(false)
                    ->setLinkAttribute('sample_key', 'sample_value'),
            ],
        ];
    }

    /**
     * @dataProvider propagateFromMenuUpdateWhenMenuItemHasValuesDataProvider
     */
    public function testPropagateFromMenuUpdateWhenMenuItemHasValues(
        ItemInterface $menuItem,
        MenuUpdateInterface $menuUpdate,
        ItemInterface $expected
    ): void {
        $this->propagator->propagateFromMenuUpdate(
            $menuItem,
            $menuUpdate,
            MenuUpdateToMenuItemPropagatorInterface::STRATEGY_BASIC
        );

        self::assertEquals($expected, $menuItem);
    }

    public function propagateFromMenuUpdateWhenMenuItemHasValuesDataProvider(): array
    {
        $menuItem = $this->createItem('sample_item')
            ->setLabel('existing label')
            ->setDisplay(true)
            ->setUri('/existing/uri')
            ->setLinkAttribute('existing_key', 'existing_value');

        return [
            'empty' => [
                'menuItem' => $menuItem,
                'menuUpdate' => new MenuUpdate(),
                'expected' => clone $menuItem,
            ],
            'with titles' => [
                'menuItem' => $menuItem,
                'menuUpdate' => (new MenuUpdate())->addTitle((new LocalizedFallbackValue())->setString('sample title')),
                'expected' => (clone $menuItem)->setLabel('sample title'),
            ],
            'with uri' => [
                'menuItem' => $menuItem,
                'menuUpdate' => (new MenuUpdate())
                    ->addTitle((new LocalizedFallbackValue())->setString('sample title'))
                    ->setUri('/sample/uri'),
                'expected' => (clone $menuItem)
                    ->setLabel('sample title')
                    ->setUri('/sample/uri'),
            ],
            'with active false' => [
                'menuItem' => $menuItem,
                'menuUpdate' => (new MenuUpdate())
                    ->addTitle((new LocalizedFallbackValue())->setString('sample title'))
                    ->setUri('/sample/uri')
                    ->setActive(false),
                'expected' => (clone $menuItem)
                    ->setLabel('sample title')
                    ->setUri('/sample/uri')
                    ->setDisplay(false),
            ],
            'with link attributes' => [
                'menuItem' => $menuItem,
                'menuUpdate' => (new MenuUpdateStub())
                    ->addTitle((new LocalizedFallbackValue())->setString('sample title'))
                    ->setUri('/sample/uri')
                    ->setActive(false)
                    ->setLinkAttributes(['sample_key' => 'sample_value']),
                'expected' => (clone $menuItem)
                    ->setLabel('sample title')
                    ->setUri('/sample/uri')
                    ->setDisplay(false)
                    ->setLinkAttribute('sample_key', 'sample_value'),
            ],
        ];
    }
}
