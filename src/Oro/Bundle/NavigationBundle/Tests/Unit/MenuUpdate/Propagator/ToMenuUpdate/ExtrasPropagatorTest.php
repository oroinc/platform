<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\MenuUpdate\Propagator\ToMenuUpdate;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\ExtrasPropagator;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\MenuItemToMenuUpdatePropagatorInterface;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ExtrasPropagatorTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private ExtrasPropagator $propagator;

    protected function setUp(): void
    {
        $this->propagator = new ExtrasPropagator(new PropertyAccessor());
        $this->propagator->setExcludeKeys(['titles']);
        $this->propagator->setMapping(['position' => 'priority']);
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
                'expected' => true,
            ],
            'full' => [
                'strategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL,
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider propagateFromMenuItemDataProvider
     */
    public function testPropagateFromMenuItem(
        MenuUpdateInterface $menuUpdate,
        ItemInterface $menuItem,
        MenuUpdateInterface $expected
    ): void {
        $this->propagator->propagateFromMenuItem(
            $menuUpdate,
            $menuItem,
            MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC
        );

        self::assertEquals($expected, $menuUpdate);
    }

    public function propagateFromMenuItemDataProvider(): array
    {
        $menuUpdate = new MenuUpdateStub();
        $menuUpdate->setDivider(false);
        $menuUpdate->setDefaultDescription('sample description');

        $menu = $this->createItem('sample_menu');
        $menu->addChild('with_excluded_key', ['extras' => ['titles' => 'sample text']]);
        $menu->addChild('with_mapped_key', ['extras' => ['position' => 42]]);
        $menu->addChild('with_non_writable_key', ['extras' => ['missing_key' => 'sample_value']]);
        $menu->addChild('with_writable_key', ['extras' => ['icon' => 'sample_icon']]);
        $menu->addChild('with_existing_value', ['extras' => ['description' => 'ignored description']]);
        $menu->addChild('with_existing_bool_value', ['extras' => ['divider' => true]]);

        return [
            'without parent' => [
                'menuUpdate' => (clone $menuUpdate)->setKey($menu->getName()),
                'menuItem' => $menu,
                'expected' => (clone $menuUpdate)->setKey($menu->getName()),
            ],
            'with excluded key' => [
                'menuUpdate' => (clone $menuUpdate)->setKey('with_excluded_key'),
                'menuItem' => $menu->getChild('with_excluded_key'),
                'expected' => (clone $menuUpdate)->setKey('with_excluded_key'),
            ],
            'with mapped key' => [
                'menuUpdate' => (clone $menuUpdate)->setKey('with_mapped_key'),
                'menuItem' => $menu->getChild('with_mapped_key'),
                'expected' => (clone $menuUpdate)->setKey('with_mapped_key')->setPriority(42),
            ],
            'with non writable key' => [
                'menuUpdate' => (clone $menuUpdate)->setKey('with_non_writable_key'),
                'menuItem' => $menu->getChild('with_non_writable_key'),
                'expected' => (clone $menuUpdate)
                    ->setKey('with_non_writable_key')
                    ->setPriority(2),
            ],
            'with writable key' => [
                'menuUpdate' => (clone $menuUpdate)->setKey('with_writable_key'),
                'menuItem' => $menu->getChild('with_writable_key'),
                'expected' => (clone $menuUpdate)
                    ->setKey('with_writable_key')
                    ->setIcon('sample_icon')
                    ->setPriority(3),
            ],
            'with existing value' => [
                'menuUpdate' => (clone $menuUpdate)->setKey('with_existing_value'),
                'menuItem' => $menu->getChild('with_existing_value'),
                'expected' => (clone $menuUpdate)->setKey('with_existing_value')
                    ->setDefaultDescription('sample description')
                    ->setPriority(4),
            ],
            'with existing bool value' => [
                'menuUpdate' => (clone $menuUpdate)->setKey('with_existing_bool_value'),
                'menuItem' => $menu->getChild('with_existing_bool_value'),
                'expected' => (clone $menuUpdate)
                    ->setKey('with_existing_bool_value')
                    ->setDivider(true)
                    ->setPriority(5),
            ],
        ];
    }
}
