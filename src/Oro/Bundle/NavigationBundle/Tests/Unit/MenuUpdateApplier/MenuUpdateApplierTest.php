<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\MenuUpdateApplier;

use Knp\Menu\ItemInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\MenuUpdateApplier\MenuUpdateApplier;
use Oro\Bundle\NavigationBundle\MenuUpdateApplier\Model\MenuUpdatesApplyResult;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Oro\Bundle\NavigationBundle\Utils\LostItemsManipulator;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MenuUpdateApplierTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject $localizationHelper;

    private MenuUpdateApplier $applier;

    protected function setUp(): void
    {
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $localizationHelper
            ->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static fn ($collection) => $collection[0] ?? null);

        $this->applier = new MenuUpdateApplier($localizationHelper);
    }

    public function testApplyMenuUpdates(): void
    {
        $menu = $this->getMenu();
        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('item-1-1-1');
        $menuUpdate->setParentKey('item-2');
        $menuUpdate->setUri('URI');
        $menuUpdate->addTitle((new LocalizedFallbackValue())->setString('sample title'));
        $menuUpdate->addDescription((new LocalizedFallbackValue())->setString('sample description'));
        $menuUpdate->setLinkAttributes(['testAttribute' => 'testValue']);

        $menuUpdatesApplyResult = $this->applier->applyMenuUpdates($menu, [$menuUpdate]);

        $expectedItem = $this->createItem('item-1-1-1');
        $expectedItem->setParent($menu->getChild('item-2'));
        $expectedItem->setUri('URI');
        $expectedItem->setLabel('sample title');
        $expectedItem->setExtra('description', 'sample description');
        $expectedItem->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuUpdate->getParentKey());
        $expectedItem->setExtra(MenuUpdateApplier::IS_CUSTOM, false);
        $expectedItem->setLinkAttribute('testAttribute', 'testValue');

        self::assertEquals($expectedItem, $item);
        self::assertEquals(
            new MenuUpdatesApplyResult($menu, [$menuUpdate], [$menuUpdate->getId() => $menuUpdate], []),
            $menuUpdatesApplyResult
        );
    }

    public function testApplyMenuUpdatesWhenNotActive(): void
    {
        $menu = $this->getMenu();

        /** @var ItemInterface $item */
        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('item-1-1-1');
        $menuUpdate->setParentKey('item-2');
        $menuUpdate->setActive(false);

        $menuUpdatesApplyResult = $this->applier->applyMenuUpdates($menu, [$menuUpdate]);

        $expectedItem = $this->createItem('item-1-1-1');
        $expectedItem->setParent($menu->getChild('item-2'));
        $expectedItem->setDisplay(false);
        $expectedItem->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuUpdate->getParentKey());
        $expectedItem->setExtra(MenuUpdateApplier::IS_CUSTOM, false);

        self::assertEquals($expectedItem, $item);
        self::assertEquals(
            new MenuUpdatesApplyResult($menu, [$menuUpdate], [$menuUpdate->getId() => $menuUpdate], []),
            $menuUpdatesApplyResult
        );
    }

    public function testApplyMenuUpdatesWhenNoLabel(): void
    {
        $menu = $this->getMenu();

        /** @var ItemInterface $item */
        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $item->setLabel('Sample original label');

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('item-1-1-1');
        $menuUpdate->setParentKey('item-2');

        $menuUpdatesApplyResult = $this->applier->applyMenuUpdates($menu, [$menuUpdate]);

        $expectedItem = $this->createItem('item-1-1-1');
        $expectedItem->setParent($menu->getChild('item-2'));
        $expectedItem->setLabel('Sample original label');
        $expectedItem->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuUpdate->getParentKey());
        $expectedItem->setExtra(MenuUpdateApplier::IS_CUSTOM, false);

        self::assertEquals($expectedItem, $item);
        self::assertEquals(
            new MenuUpdatesApplyResult($menu, [$menuUpdate], [$menuUpdate->getId() => $menuUpdate], []),
            $menuUpdatesApplyResult
        );
    }

    public function testApplyMenuUpdatesWhenNoDescription(): void
    {
        $menu = $this->getMenu();

        /** @var ItemInterface $item */
        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $item->setExtra('description', 'Sample original description');

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('item-1-1-1');
        $menuUpdate->setParentKey('item-2');

        $menuUpdatesApplyResult = $this->applier->applyMenuUpdates($menu, [$menuUpdate]);

        $expectedItem = $this->createItem('item-1-1-1');
        $expectedItem->setParent($menu->getChild('item-2'));
        $expectedItem->setExtra('description', 'Sample original description');
        $expectedItem->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuUpdate->getParentKey());
        $expectedItem->setExtra(MenuUpdateApplier::IS_CUSTOM, false);

        self::assertEquals($expectedItem, $item);
        self::assertEquals(
            new MenuUpdatesApplyResult($menu, [$menuUpdate], [$menuUpdate->getId() => $menuUpdate], []),
            $menuUpdatesApplyResult
        );
    }

    public function testApplyMenuUpdatesWhenEmptyDescription(): void
    {
        $menu = $this->getMenu();

        /** @var ItemInterface $item */
        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $item->setExtra('description', 'Sample original description');

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('item-1-1-1');
        $menuUpdate->setParentKey('item-2');
        $menuUpdate->addDescription((new LocalizedFallbackValue())->setString(''));

        $menuUpdatesApplyResult = $this->applier->applyMenuUpdates($menu, [$menuUpdate]);

        $expectedItem = $this->createItem('item-1-1-1');
        $expectedItem->setParent($menu->getChild('item-2'));
        $expectedItem->setExtra('description', 'Sample original description');
        $expectedItem->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuUpdate->getParentKey());
        $expectedItem->setExtra(MenuUpdateApplier::IS_CUSTOM, false);

        self::assertEquals($expectedItem, $item);
        self::assertEquals(
            new MenuUpdatesApplyResult($menu, [$menuUpdate], [$menuUpdate->getId() => $menuUpdate], []),
            $menuUpdatesApplyResult
        );
    }

    public function testApplyMenuUpdatesWhenNoTargetItem(): void
    {
        $menu = $this->getMenu();

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('item-1-1-1-1');
        $menuUpdate->setParentKey('item-2');
        $menuUpdate->setUri('URI');

        $menuUpdatesApplyResult = $this->applier->applyMenuUpdates($menu, [$menuUpdate]);

        $lostItemsContainer = LostItemsManipulator::getLostItemsContainer($menu, false);
        self::assertNotNull($lostItemsContainer);

        $expectedItem = $this->createItem('item-1-1-1-1');
        $expectedItem->setUri('URI');
        $expectedItem->setParent($lostItemsContainer);
        $expectedItem->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuUpdate->getParentKey());
        $expectedItem->setExtra(MenuUpdateApplier::IS_CUSTOM, false);

        self::assertNull($menu->getChild('item-2')->getChild('item-1-1-1-1'));
        self::assertEquals($expectedItem, $lostItemsContainer->getChild('item-1-1-1-1'));

        self::assertEquals(
            new MenuUpdatesApplyResult($menu, [$menuUpdate], [], [$menuUpdate->getId() => $menuUpdate]),
            $menuUpdatesApplyResult
        );
    }

    public function testApplyMenuUpdatesWhenNoTargetItemButIsCustom(): void
    {
        $menu = $this->getMenu();

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('item-1-1-1-1');
        $menuUpdate->setParentKey('item-2');
        $menuUpdate->setUri('URI');
        $menuUpdate->setCustom(true);

        $menuUpdatesApplyResult = $this->applier->applyMenuUpdates($menu, [$menuUpdate]);

        $expectedItem = $this->createItem('item-1-1-1-1');
        $expectedItem->setParent($menu->getChild('item-2'));
        $expectedItem->setUri('URI');
        $expectedItem->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuUpdate->getParentKey());
        $expectedItem->setExtra(MenuUpdateApplier::IS_CUSTOM, true);

        self::assertEquals($expectedItem, $menu->getChild('item-2')->getChild('item-1-1-1-1'));

        self::assertEquals(
            new MenuUpdatesApplyResult($menu, [$menuUpdate], [$menuUpdate->getId() => $menuUpdate], []),
            $menuUpdatesApplyResult
        );
    }

    public function testApplyMenuUpdatesWhenNoTargetItemButIsCustomAndWithOptions(): void
    {
        $menu = $this->getMenu();

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('new');
        $menuUpdate->setParentKey('item-1');
        $menuUpdate->setUri('URI');
        $menuUpdate->setCustom(true);

        $options = ['extras' => ['sample_key' => 'sample_value']];
        $menuUpdatesApplyResult = $this->applier->applyMenuUpdates($menu, [$menuUpdate], $options);

        $expectedItem = $this->createItem('new');
        $expectedItem->setParent($menu->getChild('item-1'));
        $expectedItem->setUri('URI');
        $expectedItem->setExtras(['sample_key' => 'sample_value']);
        $expectedItem->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuUpdate->getParentKey());
        $expectedItem->setExtra(MenuUpdateApplier::IS_CUSTOM, true);

        self::assertEquals($expectedItem, $menu->getChild('item-1')->getChild('new'));

        self::assertEquals(
            new MenuUpdatesApplyResult($menu, [$menuUpdate], [$menuUpdate->getId() => $menuUpdate], []),
            $menuUpdatesApplyResult
        );
    }

    public function testApplyMenuUpdatesWhenNoTargetItemAndParentButIsCustom(): void
    {
        $menu = $this->getMenu();

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('item-1-1-1-1');
        $menuUpdate->setParentKey('non-existing');
        $menuUpdate->setUri('URI');
        $menuUpdate->setCustom(true);

        $menuUpdatesApplyResult = $this->applier->applyMenuUpdates($menu, [$menuUpdate]);

        $lostItemsContainer = LostItemsManipulator::getLostItemsContainer($menu, false);
        self::assertNotNull($lostItemsContainer);

        $expectedItem = $this->createItem('item-1-1-1-1');
        $expectedItem->setParent($lostItemsContainer);
        $expectedItem->setUri('URI');
        $expectedItem->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuUpdate->getParentKey());
        $expectedItem->setExtra(MenuUpdateApplier::IS_CUSTOM, true);

        self::assertNull($menu->getChild('item-1-1-1-1'));
        self::assertEquals($expectedItem, LostItemsManipulator::getLostItemsContainer($menu)->getChild('item-1-1-1-1'));

        self::assertEquals(
            new MenuUpdatesApplyResult(
                $menu,
                [$menuUpdate],
                [$menuUpdate->getId() => $menuUpdate],
                [$menuUpdate->getId() => $menuUpdate]
            ),
            $menuUpdatesApplyResult
        );
    }

    public function testApplyMenuUpdatesWhenNoTargetItemAndCustomParent(): void
    {
        $menu = $this->getMenu();

        $menuUpdate1 = new MenuUpdateStub(42);
        $menuUpdate1->setKey('custom-parent');
        $menuUpdate1->setParentKey('item-1');
        $menuUpdate1->setCustom(true);

        $menuUpdate2 = new MenuUpdateStub(142);
        $menuUpdate2->setKey('item-1-1-1-1');
        $menuUpdate2->setParentKey('custom-parent');
        $menuUpdate2->setCustom(true);

        $menuUpdatesApplyResult = $this->applier->applyMenuUpdates($menu, [$menuUpdate1, $menuUpdate2]);

        $expectedItem = $this->createItem($menuUpdate1->getKey());
        $expectedItem->setParent($menu->getChild($menuUpdate1->getParentKey()));
        $expectedItem->addChild($menuUpdate2->getKey())
            ->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuUpdate2->getParentKey())
            ->setExtra(MenuUpdateApplier::IS_CUSTOM, true);
        $expectedItem->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuUpdate1->getParentKey());
        $expectedItem->setExtra(MenuUpdateApplier::IS_CUSTOM, true);

        self::assertEquals(
            $expectedItem,
            $menu->getChild($menuUpdate1->getParentKey())->getChild($menuUpdate1->getKey())
        );

        self::assertEquals(
            new MenuUpdatesApplyResult(
                $menu,
                [$menuUpdate1, $menuUpdate2],
                [$menuUpdate1->getId() => $menuUpdate1, $menuUpdate2->getId() => $menuUpdate2],
                []
            ),
            $menuUpdatesApplyResult
        );
    }

    public function testApplyMenuUpdatesWhenNoTargetItemAndCustomParentCreatedAfterwards(): void
    {
        $menu = $this->getMenu();

        $menuUpdate1 = new MenuUpdateStub(42);
        $menuUpdate1->setKey('item-1-1-1-1');
        $menuUpdate1->setParentKey('custom-parent');
        $menuUpdate1->setCustom(true);

        $menuUpdate2 = new MenuUpdateStub(142);
        $menuUpdate2->setKey('custom-parent');
        $menuUpdate2->setParentKey('item-1');
        $menuUpdate2->setCustom(true);

        $menuUpdatesApplyResult = $this->applier->applyMenuUpdates($menu, [$menuUpdate1, $menuUpdate2]);

        $expectedItem = $this->createItem($menuUpdate2->getKey());
        $expectedItem->setParent($menu->getChild($menuUpdate2->getParentKey()));
        $expectedItem->addChild($menuUpdate1->getKey())
            ->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuUpdate1->getParentKey())
            ->setExtra(MenuUpdateApplier::IS_CUSTOM, true);
        $expectedItem->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuUpdate2->getParentKey());
        $expectedItem->setExtra(MenuUpdateApplier::IS_CUSTOM, true);

        self::assertEquals(
            $expectedItem,
            $menu->getChild($menuUpdate2->getParentKey())->getChild($menuUpdate2->getKey())
        );

        self::assertEquals(
            new MenuUpdatesApplyResult(
                $menu,
                [$menuUpdate1, $menuUpdate2],
                [$menuUpdate1->getId() => $menuUpdate1, $menuUpdate2->getId() => $menuUpdate2],
                [],
                []
            ),
            $menuUpdatesApplyResult
        );
    }

    public function testApplyMenuUpdatesWhenMultiple(): void
    {
        $menu = $this->getMenu();

        $menuUpdate1 = new MenuUpdateStub(42);
        $menuUpdate1->setKey('item-1-1-1-1');
        $menuUpdate1->setParentKey('item-1');
        $menuUpdate1->addTitle((new LocalizedFallbackValue())->setString('Sample title'));
        $menuUpdate1->setCustom(true);

        $menuUpdate2 = new MenuUpdateStub(142);
        $menuUpdate2->setKey('item-1-1-1-1');
        $menuUpdate2->setParentKey('item-2');
        $menuUpdate2->addTitle((new LocalizedFallbackValue())->setString('Sample updated title'));
        $menuUpdate2->setCustom(true);

        $menuUpdatesApplyResult = $this->applier->applyMenuUpdates($menu, [$menuUpdate1, $menuUpdate2]);

        $expectedItem = $this->createItem($menuUpdate2->getKey());
        $expectedItem->setParent($menu->getChild($menuUpdate2->getParentKey()));
        $expectedItem->setLabel('Sample updated title');
        $expectedItem->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuUpdate2->getParentKey());
        $expectedItem->setExtra(MenuUpdateApplier::IS_CUSTOM, true);

        self::assertEquals(
            $expectedItem,
            $menu->getChild($menuUpdate2->getParentKey())->getChild($menuUpdate2->getKey())
        );

        self::assertEquals(
            new MenuUpdatesApplyResult(
                $menu,
                [$menuUpdate1, $menuUpdate2],
                [$menuUpdate1->getId() => $menuUpdate1, $menuUpdate2->getId() => $menuUpdate2],
                []
            ),
            $menuUpdatesApplyResult
        );
    }
}
