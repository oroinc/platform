<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\MenuUpdateApplier\Model;

use Oro\Bundle\NavigationBundle\MenuUpdateApplier\Model\MenuUpdatesApplyResult;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;

class MenuUpdatesApplyResultTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    public function testGetters(): void
    {
        $menu = $this->createItem('sample_menu');
        $menuUpdate1 = new MenuUpdateStub(10);
        $menuUpdate2 = new MenuUpdateStub(20);
        $menuUpdate3 = new MenuUpdateStub(30);

        $model = new MenuUpdatesApplyResult(
            $menu,
            [$menuUpdate1, $menuUpdate2, $menuUpdate3],
            [
                $menuUpdate1->getId() => $menuUpdate1,
                $menuUpdate2->getId() => $menuUpdate2,
                $menuUpdate3->getId() => $menuUpdate3,
            ],
            [$menuUpdate3->getId() => $menuUpdate3]
        );

        self::assertSame($menu, $model->getMenu());
        self::assertSame([$menuUpdate1, $menuUpdate2, $menuUpdate3], $model->getAllMenuUpdates());
        self::assertSame(
            [
                $menuUpdate1->getId() => $menuUpdate1,
                $menuUpdate2->getId() => $menuUpdate2,
                $menuUpdate3->getId() => $menuUpdate3,
            ],
            $model->getAppliedMenuUpdates()
        );
        self::assertSame([$menuUpdate3->getId() => $menuUpdate3], $model->getLostMenuUpdates());
    }
}
