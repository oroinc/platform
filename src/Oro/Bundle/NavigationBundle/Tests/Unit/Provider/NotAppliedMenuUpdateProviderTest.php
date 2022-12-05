<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Oro\Bundle\NavigationBundle\Event\MenuUpdatesApplyAfterEvent;
use Oro\Bundle\NavigationBundle\MenuUpdateApplier\Model\MenuUpdatesApplyResult;
use Oro\Bundle\NavigationBundle\Provider\NotAppliedMenuUpdateProvider;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;

class NotAppliedMenuUpdateProviderTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private NotAppliedMenuUpdateProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new NotAppliedMenuUpdateProvider();
    }

    public function testGetMenuUpdatesForMenuItemWhenNoUpdates(): void
    {
        self::assertEquals([], $this->provider->getMenuUpdatesForMenuItem($this->createItem('sample_menu')));
    }

    public function testGetMenuUpdatesForMenuItemWhenNoNotAppliedMenuUpdates(): void
    {
        $menu = $this->createItem('sample_menu');
        $menuUpdate1 = new MenuUpdateStub(42);
        $event = new MenuUpdatesApplyAfterEvent(new MenuUpdatesApplyResult($menu, [$menuUpdate1], [], [], []));
        $this->provider->onMenuUpdatesApplyAfter($event);

        self::assertEquals([], $this->provider->getMenuUpdatesForMenuItem($menu));
    }

    public function testGetMenuUpdatesForMenuItemWhenOnlyNotAppliedMenuUpdates(): void
    {
        $menu = $this->createItem('sample_menu');
        $menuUpdate1 = new MenuUpdateStub(42);
        $event = new MenuUpdatesApplyAfterEvent(
            new MenuUpdatesApplyResult($menu, [], [], [$menuUpdate1->getId() => $menuUpdate1], [])
        );
        $this->provider->onMenuUpdatesApplyAfter($event);

        self::assertEquals(
            $event->getApplyResult()->getNotAppliedMenuUpdates(),
            $this->provider->getMenuUpdatesForMenuItem($menu)
        );
    }

    public function testGetMenuUpdatesForMenuItemWhenOnlyOrphanMenuUpdates(): void
    {
        $menu = $this->createItem('sample_menu');
        $menuUpdate1 = new MenuUpdateStub(42);
        $event = new MenuUpdatesApplyAfterEvent(
            new MenuUpdatesApplyResult($menu, [], [], [], [$menuUpdate1->getId() => $menuUpdate1])
        );
        $this->provider->onMenuUpdatesApplyAfter($event);

        self::assertEquals(
            $event->getApplyResult()->getOrphanMenuUpdates(),
            $this->provider->getMenuUpdatesForMenuItem($menu)
        );
    }

    public function testGetMenuUpdatesForMenuItemWhenNotAppliedAndOrphanMenuUpdates(): void
    {
        $menu = $this->createItem('sample_menu');
        $menuUpdate1 = new MenuUpdateStub(42);
        $menuUpdate2 = new MenuUpdateStub(142);
        $event = new MenuUpdatesApplyAfterEvent(
            new MenuUpdatesApplyResult(
                $menu,
                [],
                [],
                [$menuUpdate1->getId() => $menuUpdate1],
                [$menuUpdate2->getId() => $menuUpdate2]
            )
        );
        $this->provider->onMenuUpdatesApplyAfter($event);

        self::assertEquals(
            $event->getApplyResult()->getNotAppliedMenuUpdates() + $event->getApplyResult()->getOrphanMenuUpdates(),
            $this->provider->getMenuUpdatesForMenuItem($menu)
        );
    }

    public function testGetMenuUpdatesForMenuItemWhenNotAppliedAndOrphanMenuUpdatesIntersect(): void
    {
        $menu = $this->createItem('sample_menu');
        $menuUpdate1 = new MenuUpdateStub(42);
        $menuUpdate2 = new MenuUpdateStub(142);
        $event = new MenuUpdatesApplyAfterEvent(
            new MenuUpdatesApplyResult(
                $menu,
                [],
                [],
                [$menuUpdate1->getId() => $menuUpdate1, $menuUpdate2->getId() => $menuUpdate2],
                [$menuUpdate2->getId() => $menuUpdate2]
            )
        );
        $this->provider->onMenuUpdatesApplyAfter($event);

        self::assertEquals(
            $event->getApplyResult()->getNotAppliedMenuUpdates() + $event->getApplyResult()->getOrphanMenuUpdates(),
            $this->provider->getMenuUpdatesForMenuItem($menu)
        );
    }

    public function testReset(): void
    {
        $menu = $this->createItem('sample_menu');
        $menuUpdate1 = new MenuUpdateStub(42);
        $menuUpdate2 = new MenuUpdateStub(142);
        $event = new MenuUpdatesApplyAfterEvent(
            new MenuUpdatesApplyResult(
                $menu,
                [],
                [],
                [$menuUpdate1->getId() => $menuUpdate1],
                [$menuUpdate2->getId() => $menuUpdate2]
            )
        );
        $this->provider->onMenuUpdatesApplyAfter($event);

        $this->provider->reset();
        self::assertEquals([], $this->provider->getMenuUpdatesForMenuItem($menu));
    }
}
