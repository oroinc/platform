<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\NavigationBundle\ContentProvider\MenuContentProvider;
use Oro\Bundle\NavigationBundle\Twig\MenuExtension;

class MenuContentProviderTest extends \PHPUnit\Framework\TestCase
{
    private const MENU = 'test';

    /** @var MenuExtension|\PHPUnit\Framework\MockObject\MockObject */
    private $menuExtension;

    /** @var MenuContentProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->menuExtension = $this->createMock(MenuExtension::class);

        $this->provider = new MenuContentProvider($this->menuExtension, self::MENU);
    }

    public function testGetContent()
    {
        $this->menuExtension->expects($this->once())
            ->method('render')
            ->with(self::MENU)
            ->willReturn('rendered_menu');

        $this->assertEquals('rendered_menu', $this->provider->getContent());
    }
}
