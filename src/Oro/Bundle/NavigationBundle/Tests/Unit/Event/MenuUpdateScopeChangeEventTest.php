<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Oro\Bundle\NavigationBundle\Event\MenuUpdateChangeEvent;
use Oro\Component\Testing\Unit\EntityTrait;

class MenuUpdateScopeChangeEventTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testGetMenuName()
    {
        $context = ['foo' => 'bar'];
        $event = new MenuUpdateChangeEvent('application_menu', $context);
        $this->assertEquals('application_menu', $event->getMenuName());
    }

    public function testGetScope()
    {
        $context = ['foo' => 'bar'];
        $event = new MenuUpdateChangeEvent('application_menu', $context);
        $this->assertSame($context, $event->getContext());
    }
}
