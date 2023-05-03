<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\MenuUpdate\Propagator\ToMenuItem;

use Knp\Menu\ItemInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem\ExtrasPropagator;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\MenuItemToMenuUpdatePropagatorInterface;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;

class ExtrasPropagatorTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private ExtrasPropagator $propagator;

    protected function setUp(): void
    {
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $localizationHelper
            ->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static fn ($collection) => $collection[0] ?? null);

        $this->propagator = new ExtrasPropagator($localizationHelper);
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
                'strategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_NONE,
                'expected' => false,
            ],
            'basic' => [
                'strategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC,
                'expected' => true,
            ],
            'full' => [
                'strategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL,
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
            MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL
        );
        self::assertEquals($expected, $menuItem);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function propagateFromMenuUpdateDataProvider(): array
    {
        $menuItem = $this->createItem('sample_item')
            ->setExtra(MenuUpdateInterface::IS_DIVIDER, false)
            ->setExtra(MenuUpdateInterface::IS_TRANSLATE_DISABLED, false)
            ->setExtra(MenuUpdateInterface::IS_CUSTOM, false)
            ->setExtra(MenuUpdateInterface::IS_SYNTHETIC, false);

        return [
            'empty' => [
                'menuUpdate' => new MenuUpdate(),
                'expected' => clone $menuItem,
            ],
            'with empty description' => [
                'menuUpdate' => (new MenuUpdateStub())
                    ->addDescription((new LocalizedFallbackValue())->setText('')),
                'expected' => clone $menuItem,
            ],
            'with description' => [
                'menuUpdate' => (new MenuUpdateStub())
                    ->addDescription((new LocalizedFallbackValue())->setText('sample description')),
                'expected' => (clone $menuItem)
                    ->setExtra(MenuUpdateInterface::DESCRIPTION, 'sample description'),
            ],
            'divider' => [
                'menuUpdate' => (new MenuUpdateStub())
                    ->addDescription((new LocalizedFallbackValue())->setText('sample description'))
                    ->setDivider(true),
                'expected' => (clone $menuItem)
                    ->setExtra(MenuUpdateInterface::DESCRIPTION, 'sample description')
                    ->setExtra(MenuUpdateInterface::IS_DIVIDER, true),
            ],
            'translate disabled' => [
                'menuUpdate' => (new MenuUpdateStub(42))
                    ->addTitle((new LocalizedFallbackValue())->setString('sample title'))
                    ->addDescription((new LocalizedFallbackValue())->setText('sample description'))
                    ->setDivider(true),
                'expected' => (clone $menuItem)
                    ->setExtra(MenuUpdateInterface::DESCRIPTION, 'sample description')
                    ->setExtra(MenuUpdateInterface::IS_DIVIDER, true)
                    ->setExtra(MenuUpdateInterface::IS_TRANSLATE_DISABLED, true),
            ],
            'custom' => [
                'menuUpdate' => (new MenuUpdateStub(42))
                    ->addTitle((new LocalizedFallbackValue())->setString('sample title'))
                    ->addDescription((new LocalizedFallbackValue())->setText('sample description'))
                    ->setDivider(true)
                    ->setCustom(true),
                'expected' => (clone $menuItem)
                    ->setExtra(MenuUpdateInterface::DESCRIPTION, 'sample description')
                    ->setExtra(MenuUpdateInterface::IS_DIVIDER, true)
                    ->setExtra(MenuUpdateInterface::IS_TRANSLATE_DISABLED, true)
                    ->setExtra(MenuUpdateInterface::IS_CUSTOM, true),
            ],
            'synthetic' => [
                'menuUpdate' => (new MenuUpdateStub(42))
                    ->addTitle((new LocalizedFallbackValue())->setString('sample title'))
                    ->addDescription((new LocalizedFallbackValue())->setText('sample description'))
                    ->setDivider(true)
                    ->setCustom(true)
                    ->setSynthetic(true),
                'expected' => (clone $menuItem)
                    ->setExtra(MenuUpdateInterface::DESCRIPTION, 'sample description')
                    ->setExtra(MenuUpdateInterface::IS_DIVIDER, true)
                    ->setExtra(MenuUpdateInterface::IS_TRANSLATE_DISABLED, true)
                    ->setExtra(MenuUpdateInterface::IS_CUSTOM, true)
                    ->setExtra(MenuUpdateInterface::IS_SYNTHETIC, true),
            ],
            'with priority' => [
                'menuUpdate' => (new MenuUpdateStub(42))
                    ->addTitle((new LocalizedFallbackValue())->setString('sample title'))
                    ->addDescription((new LocalizedFallbackValue())->setText('sample description'))
                    ->setDivider(true)
                    ->setCustom(true)
                    ->setSynthetic(true)
                    ->setPriority(42),
                'expected' => (clone $menuItem)
                    ->setExtra(MenuUpdateInterface::DESCRIPTION, 'sample description')
                    ->setExtra(MenuUpdateInterface::IS_DIVIDER, true)
                    ->setExtra(MenuUpdateInterface::IS_TRANSLATE_DISABLED, true)
                    ->setExtra(MenuUpdateInterface::IS_CUSTOM, true)
                    ->setExtra(MenuUpdateInterface::IS_SYNTHETIC, true)
                    ->setExtra(MenuUpdateInterface::POSITION, 42),
            ],
            'with icon' => [
                'menuUpdate' => (new MenuUpdateStub(42))
                    ->addTitle((new LocalizedFallbackValue())->setString('sample title'))
                    ->addDescription((new LocalizedFallbackValue())->setText('sample description'))
                    ->setDivider(true)
                    ->setCustom(true)
                    ->setSynthetic(true)
                    ->setPriority(42)
                    ->setIcon('sample-icon'),
                'expected' => (clone $menuItem)
                    ->setExtra(MenuUpdateInterface::DESCRIPTION, 'sample description')
                    ->setExtra(MenuUpdateInterface::IS_DIVIDER, true)
                    ->setExtra(MenuUpdateInterface::IS_TRANSLATE_DISABLED, true)
                    ->setExtra(MenuUpdateInterface::IS_CUSTOM, true)
                    ->setExtra(MenuUpdateInterface::IS_SYNTHETIC, true)
                    ->setExtra(MenuUpdateInterface::POSITION, 42)
                    ->setExtra(MenuUpdateInterface::ICON, 'sample-icon'),
            ],
        ];
    }
}
