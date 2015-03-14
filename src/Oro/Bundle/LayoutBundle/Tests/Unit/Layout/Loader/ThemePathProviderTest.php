<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Loader;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\LayoutBundle\Layout\Loader\ThemeAndRoutePathProvider;

/**
 * @property ThemeAndRoutePathProvider provider
 */
class ThemeAndRoutePathProviderTest extends AbstractPathProviderTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->provider = new ThemeAndRoutePathProvider($this->themeManager);
    }

    public function testRootThemePathVote()
    {
        $context = new LayoutContext();
        $context->set('theme', 'black');
        $this->setUpThemeManager(['black' => $this->getThemeMock('black')]);

        $this->provider->setContext($context);
        $this->assertSame(['black'], $this->provider->getPaths());
    }

    public function testThemeHierarchyWithRoutePassed()
    {
        $context = new LayoutContext();
        $context->set('theme', 'black');
        $context->set('route_name', 'oro_route');
        $this->setUpThemeManager(
            ['black' => $this->getThemeMock('black', 'base'), 'base' => $this->getThemeMock('base')]
        );

        $this->provider->setContext($context);
        $this->assertSame(['base', 'black', 'base/oro_route', 'black/oro_route'], $this->provider->getPaths());
    }
}
