<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Oro\Bundle\NavigationBundle\Event\BeforeMenuHandleUpdateEvent;
use PHPUnit\Framework\TestCase;

class BeforeMenuHandleUpdateEventTest extends TestCase
{
    public function testName(): void
    {
        self::assertEquals('oro_menu.before_menu_handle_update', BeforeMenuHandleUpdateEvent::NAME);
    }

    public function testCreate(): void
    {
        $menuName = 'menu_name';
        $context = ['some_options' => 'some_value'];

        $event = new BeforeMenuHandleUpdateEvent($menuName, $context);

        self::assertEquals($menuName, $event->getMenuName());
        self::assertSame($context, $event->getContext());
    }
}
