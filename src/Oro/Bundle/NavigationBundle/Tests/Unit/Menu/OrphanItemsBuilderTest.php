<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Event\MenuUpdatesApplyAfterEvent;
use Oro\Bundle\NavigationBundle\Menu\OrphanItemsBuilder;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\Model\MenuUpdateApplierContext;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;

class OrphanItemsBuilderTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private OrphanItemsBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new OrphanItemsBuilder();
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

    public function testWhenNoOrphanItems(): void
    {
        $menu = $this->createItem('sample_menu');

        $context = new MenuUpdateApplierContext($menu);
        $this->builder->onMenuUpdatesApplyAfter(new MenuUpdatesApplyAfterEvent($context));
        $this->builder->build($menu);
    }

    public function testWhenHasOrphanItem(): void
    {
        $menu = $this->createItem('sample_menu');
        $sampleItem = $menu->addChild('sample_item');
        $orphanItem = $menu->addChild('orphan_item');

        $context = new MenuUpdateApplierContext($menu);
        $context->addOrphanedItem($sampleItem->getName(), $orphanItem, $this->createMock(MenuUpdateInterface::class));
        $this->builder->onMenuUpdatesApplyAfter(new MenuUpdatesApplyAfterEvent($context));
        $this->builder->build($menu);

        self::assertEmpty($context->getOrphanedItems($sampleItem->getName()));
        self::assertSame([$sampleItem->getName() => $sampleItem], $menu->getChildren());
        self::assertSame([$orphanItem->getName() => $orphanItem], $sampleItem->getChildren());
    }

    public function testWhenHasOrphanItemButNoParent(): void
    {
        $menu = $this->createItem('sample_menu');
        $sampleItem = $menu->addChild('sample_item');
        $orphanItem = $menu->addChild('orphan_item');

        $context = new MenuUpdateApplierContext($menu);
        $context->addOrphanedItem('non_existing', $orphanItem, $this->createMock(MenuUpdateInterface::class));
        $this->builder->onMenuUpdatesApplyAfter(new MenuUpdatesApplyAfterEvent($context));
        $this->builder->build($menu);

        self::assertNotEmpty($context->getOrphanedItems('non_existing'));
        self::assertSame(
            [$sampleItem->getName() => $sampleItem, $orphanItem->getName() => $orphanItem],
            $menu->getChildren()
        );
        self::assertSame([], $sampleItem->getChildren());
    }

    public function testWhenHasOrphanItemButIsLost(): void
    {
        $menu = $this->createItem('sample_menu');
        $sampleItem = $menu->addChild('sample_item');
        $orphanItem = $menu->addChild('orphan_item');

        $context = new MenuUpdateApplierContext($menu);
        $context->addOrphanedItem($sampleItem->getName(), $orphanItem, $this->createMock(MenuUpdateInterface::class));
        $context->addLostItem($orphanItem, $this->createMock(MenuUpdateInterface::class));
        $this->builder->onMenuUpdatesApplyAfter(new MenuUpdatesApplyAfterEvent($context));
        $this->builder->build($menu);

        self::assertNotEmpty($context->getOrphanedItems($sampleItem->getName()));
        self::assertSame(
            [$sampleItem->getName() => $sampleItem, $orphanItem->getName() => $orphanItem],
            $menu->getChildren()
        );
        self::assertSame([], $sampleItem->getChildren());
    }
}
