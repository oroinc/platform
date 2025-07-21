<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity;

use Oro\Bundle\NavigationBundle\Entity\NavigationItem;
use Oro\Bundle\NavigationBundle\Entity\PinbarTab;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class PinbarTabTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testSetMaximizedNotEmpty(): void
    {
        $item = $this->createMock(NavigationItem::class);

        $pinbarTab = new PinbarTab();
        $pinbarTab->setItem($item);
        $pinbarTab->setMaximized('2022-02-02 22:22:22');

        $this->assertInstanceOf('DateTime', $pinbarTab->getMaximized());
    }

    public function testSetMaximizedEmpty(): void
    {
        $item = $this->createMock(NavigationItem::class);

        $pinbarTab = new PinbarTab();
        $pinbarTab->setItem($item);
        $pinbarTab->setMaximized('');

        $this->assertNull($pinbarTab->getMaximized());
    }

    public function testSetGet(): void
    {
        $item = $this->createMock(NavigationItem::class);

        $this->assertPropertyAccessors(
            new PinbarTab(),
            [
                ['item', $item, null],
                ['title', 'sample-title', null],
                ['titleShort', 'sample-title-short', null],
            ]
        );
    }

    public function testDoPrePersist(): void
    {
        $item = $this->createMock(NavigationItem::class);

        $pinbarTab = new PinbarTab();
        $pinbarTab->setItem($item);
        $pinbarTab->doPrePersist();

        $this->assertNull($pinbarTab->getMaximized());
    }

    public function testSetValues(): void
    {
        $values = ['maximized' => '2022-02-02 22:22:22', 'url' => '/'];
        $item = $this->createMock(NavigationItem::class);
        $item->expects($this->once())
            ->method('setValues')
            ->with($values);
        $pinbarTab = new PinbarTab();
        $pinbarTab->setItem($item);
        $pinbarTab->setValues($values);
        $this->assertInstanceOf('DateTime', $pinbarTab->getMaximized());
    }

    public function testGetUserNoItem(): void
    {
        $pinbarTab = new PinbarTab();
        $this->assertNull($pinbarTab->getUser());
    }

    public function testGetUser(): void
    {
        $user = $this->createMock(\stdClass::class);
        $item = $this->createMock(NavigationItem::class);
        $item->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $pinbarTab = new PinbarTab();
        $pinbarTab->setItem($item);
        $this->assertSame($user, $pinbarTab->getUser());
    }
}
