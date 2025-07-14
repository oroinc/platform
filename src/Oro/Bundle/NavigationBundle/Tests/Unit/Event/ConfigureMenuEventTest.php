<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigureMenuEventTest extends TestCase
{
    private FactoryInterface&MockObject $factory;
    private ItemInterface&MockObject $menu;
    private ConfigureMenuEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = $this->createMock(FactoryInterface::class);
        $this->menu = $this->createMock(ItemInterface::class);

        $this->event = new ConfigureMenuEvent($this->factory, $this->menu);
    }

    public function testGetFactory(): void
    {
        $this->assertEquals($this->event->getFactory(), $this->factory);
    }

    public function testGetMenu(): void
    {
        $this->assertEquals($this->event->getMenu(), $this->menu);
    }

    public function testGetEventName(): void
    {
        $this->assertEquals('oro_menu.configure.test', ConfigureMenuEvent::getEventName('test'));
    }
}
