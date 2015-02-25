<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Layout;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\NavigationBundle\Layout\TitleContextConfigurator;

class TitleContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $titleProvider;

    /** @var TitleContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->titleProvider = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Provider\TitleProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextConfigurator = new TitleContextConfigurator($this->titleProvider);
    }

    public function testConfigureContext()
    {
        $routeName          = 'test_route';
        $titleTemplate      = 'test title';
        $shortTitleTemplate = 'test short title';

        $context = new LayoutContext();
        $context->getDataResolver()->setOptional(['route_name']);
        $context['route_name'] = $routeName;

        $this->titleProvider->expects($this->once())
            ->method('getTitleTemplates')
            ->with($routeName)
            ->will($this->returnValue(['title' => $titleTemplate, 'short_title' => $shortTitleTemplate]));

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertSame($titleTemplate, $context['title_template']);
    }

    public function testConfigureContextWhenTitleDoesNotExist()
    {
        $routeName          = 'test_route';

        $context = new LayoutContext();
        $context->getDataResolver()->setOptional(['route_name']);
        $context['route_name'] = $routeName;

        $this->titleProvider->expects($this->once())
            ->method('getTitleTemplates')
            ->with($routeName)
            ->will($this->returnValue([]));

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertSame('', $context['title_template']);
    }

    public function testConfigureContextWithoutRoute()
    {
        $context = new LayoutContext();

        $this->titleProvider->expects($this->never())
            ->method('getTitleTemplates');

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertSame('', $context['title_template']);
    }

    public function testConfigureContextOverride()
    {
        $routeName = 'test_route';

        $context = new LayoutContext();
        $context->getDataResolver()->setOptional(['route_name']);
        $context['route_name'] = $routeName;

        $context['title_template'] = 'my title';

        $this->titleProvider->expects($this->never())
            ->method('getTitleTemplates');

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertSame('my title', $context['title_template']);
    }
}
