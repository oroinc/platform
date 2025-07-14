<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Oro\Bundle\NavigationBundle\Event\MenuUpdateChangeEvent;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class MenuUpdateScopeChangeEventTest extends TestCase
{
    use EntityTrait;

    public function testGetMenuName(): void
    {
        $context = ['foo' => 'bar'];
        $event = new MenuUpdateChangeEvent('application_menu', $context);
        $this->assertEquals('application_menu', $event->getMenuName());
    }

    public function testGetScope(): void
    {
        $context = ['foo' => 'bar'];
        $event = new MenuUpdateChangeEvent('application_menu', $context);
        $this->assertSame($context, $event->getContext());
    }
}
