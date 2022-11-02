<?php
namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;

class ConfigureMenuEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var FactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $factory;

    /** @var ItemInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $menu;

    /** @var ConfigureMenuEvent */
    private $event;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(FactoryInterface::class);
        $this->menu = $this->createMock(ItemInterface::class);

        $this->event = new ConfigureMenuEvent($this->factory, $this->menu);
    }

    public function testGetFactory()
    {
        $this->assertEquals($this->event->getFactory(), $this->factory);
    }

    public function testGetMenu()
    {
        $this->assertEquals($this->event->getMenu(), $this->menu);
    }

    public function testGetEventName()
    {
        $this->assertEquals('oro_menu.configure.test', ConfigureMenuEvent::getEventName('test'));
    }
}
