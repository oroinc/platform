<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\UIBundle\Layout\Extension\WidgetContextConfigurator;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WidgetContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var WidgetContextConfigurator */
    protected $contextConfigurator;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    protected function setUp()
    {
        $this->requestStack = new RequestStack();
        $this->contextConfigurator = new WidgetContextConfigurator($this->requestStack);
    }

    public function testConfigureContextByQueryString()
    {
        $request = new Request();
        $request->query->set('_wid', 'test_widget_id');
        $request->query->set('_widgetContainer', 'dialog');
        $this->requestStack->push($request);

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);

        $context->resolve();

        $this->assertEquals('dialog', $context['widget_container']);
        $this->assertEquals('test_widget_id', $context->data()->get('widget_id'));
    }

    public function testConfigureContextByPostData()
    {
        $request = new Request();
        $request->request->set('_wid', 'test_widget_id');
        $request->request->set('_widgetContainer', 'dialog');
        $this->requestStack->push($request);

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals('dialog', $context['widget_container']);
        $this->assertEquals('test_widget_id', $context->data()->get('widget_id'));
    }

    public function testConfigureContextNoWidget()
    {
        $request = new Request();
        $this->requestStack->push($request);

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertNull($context['widget_container']);
        $this->assertNull($context->data()->get('widget_id'));
    }

    public function testConfigureContextOverride()
    {
        $request = new Request();
        $request->query->set('_wid', 'test_widget_id');
        $request->query->set('_widgetContainer', 'dialog');
        $this->requestStack->push($request);

        $context                     = new LayoutContext();
        $context['widget_container'] = 'updated_widget';
        $context->data()->set('widget_id', 'updated_widget_id');

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals('updated_widget', $context['widget_container']);
        $this->assertEquals('updated_widget_id', $context->data()->get('widget_id'));
    }

    public function testConfigureContextWithoutRequest()
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertNull($context['widget_container']);
        $this->assertFalse($context->data()->has('widget_id'));
    }
}
