<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\NavigationBundle\ContentProvider\MenuContentProvider;
use Oro\Bundle\NavigationBundle\Twig\MenuExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MenuContentProviderTest extends TestCase
{
    private const MENU = 'test';

    private MenuExtension&MockObject $menuExtension;
    private MenuContentProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->menuExtension = $this->createMock(MenuExtension::class);

        $this->provider = new MenuContentProvider($this->menuExtension, self::MENU);
    }

    public function testGetContent(): void
    {
        $this->menuExtension->expects($this->once())
            ->method('render')
            ->with(self::MENU)
            ->willReturn('rendered_menu');

        $this->assertEquals('rendered_menu', $this->provider->getContent());
    }
}
