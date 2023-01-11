<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Event\MenuUpdatesApplyAfterEvent;
use Oro\Bundle\NavigationBundle\Menu\LostItemsBuilder;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\Model\MenuUpdateApplierContext;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;

class LostItemsBuilderTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private LostItemsBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new LostItemsBuilder();
    }

    public function testWhenNotDisplayed(): void
    {
        $menu = $this->createItem('sample_menu')
            ->setDisplay(false);

        $this->builder->build($menu);
    }

    public function testWhenNoMenuUpdateApplierContext(): void
    {
        $menu = $this->createItem('sample_menu');

        $this->builder->build($menu);
    }

    public function testWhenNoLostItems(): void
    {
        $menu = $this->createItem('sample_menu');

        $context = new MenuUpdateApplierContext($menu);
        $this->builder->onMenuUpdatesApplyAfter(new MenuUpdatesApplyAfterEvent($context));
        $this->builder->build($menu);
    }

    public function testWhenHasLostItem(): void
    {
        $menu = $this->createItem('sample_menu');
        $sampleItem = $menu->addChild('sample_item');
        $lostItem = $menu->addChild('lost_item');

        $context = new MenuUpdateApplierContext($menu);
        $context->addLostItem($lostItem, $this->createMock(MenuUpdateInterface::class));
        $this->builder->onMenuUpdatesApplyAfter(new MenuUpdatesApplyAfterEvent($context));
        $this->builder->build($menu);

        self::assertSame([$sampleItem->getName() => $sampleItem], $menu->getChildren());
    }

    public function testWhenHasLostItemWithCustomItemInside(): void
    {
        $menu = $this->createItem('sample_menu');
        $sampleItem = $menu->addChild('sample_item');
        $lostItem = $menu->addChild('lost_item');
        $customItem = $lostItem->addChild('custom_item', ['extras' => [MenuUpdateInterface::IS_CUSTOM => true]]);

        $context = new MenuUpdateApplierContext($menu);
        $context->addLostItem($lostItem, $this->createMock(MenuUpdateInterface::class));
        $this->builder->onMenuUpdatesApplyAfter(new MenuUpdatesApplyAfterEvent($context));
        $this->builder->build($menu);

        self::assertSame(
            [$sampleItem->getName() => $sampleItem, $customItem->getName() => $customItem],
            $menu->getChildren()
        );
    }

    public function testWhenHasLostItemWithSyntheticItemInside(): void
    {
        $menu = $this->createItem('sample_menu');
        $sampleItem = $menu->addChild('sample_item');
        $lostItem = $menu->addChild('lost_item');
        $syntheticItem = $lostItem->addChild(
            'synthetic_item',
            ['extras' => [MenuUpdateInterface::IS_SYNTHETIC => true]]
        );

        $context = new MenuUpdateApplierContext($menu);
        $context->addLostItem($lostItem, $this->createMock(MenuUpdateInterface::class));
        $this->builder->onMenuUpdatesApplyAfter(new MenuUpdatesApplyAfterEvent($context));
        $this->builder->build($menu);

        self::assertSame(
            [$sampleItem->getName() => $sampleItem, $syntheticItem->getName() => $syntheticItem],
            $menu->getChildren()
        );
    }
}
