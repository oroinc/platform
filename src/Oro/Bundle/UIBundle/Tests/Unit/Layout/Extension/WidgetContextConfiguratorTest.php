<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\UIBundle\Layout\Extension\WidgetContextConfigurator;

class WidgetContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var WidgetContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->contextConfigurator = new WidgetContextConfigurator();
    }

    public function testConfigureContextByQueryString()
    {
        $request = new Request();
        $request->query->set('_wid', 'test_widget_id');
        $request->query->set('_widgetContainer', 'dialog');

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $this->contextConfigurator->setRequest($request);
        $context->resolve();

        $this->assertEquals('dialog', $context['widget_container']);
        $this->assertEquals('$request._wid', $context->data()->getIdentifier('widget_id'));
        $this->assertEquals('test_widget_id', $context->data()->get('widget_id'));
    }

    public function testConfigureContextByPostData()
    {
        $request = new Request();
        $request->request->set('_wid', 'test_widget_id');
        $request->request->set('_widgetContainer', 'dialog');

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $this->contextConfigurator->setRequest($request);
        $context->resolve();

        $this->assertEquals('dialog', $context['widget_container']);
        $this->assertEquals('$request._wid', $context->data()->getIdentifier('widget_id'));
        $this->assertEquals('test_widget_id', $context->data()->get('widget_id'));
    }

    public function testConfigureContextNoWidget()
    {
        $request = new Request();

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $this->contextConfigurator->setRequest($request);
        $context->resolve();

        $this->assertNull($context['widget_container']);
        $this->assertEquals('$request._wid', $context->data()->getIdentifier('widget_id'));
        $this->assertNull($context->data()->get('widget_id'));
    }

    public function testConfigureContextOverride()
    {
        $request = new Request();
        $request->query->set('_wid', 'test_widget_id');
        $request->query->set('_widgetContainer', 'dialog');

        $context                     = new LayoutContext();
        $context['widget_container'] = 'updated_widget';
        $context->data()->set('widget_id', 'updated_id', 'updated_widget_id');

        $this->contextConfigurator->configureContext($context);
        $this->contextConfigurator->setRequest($request);
        $context->resolve();

        $this->assertEquals('updated_widget', $context['widget_container']);
        $this->assertEquals('updated_id', $context->data()->getIdentifier('widget_id'));
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

    public function testRequestSetterSynchronized()
    {
        $this->contextConfigurator->setRequest(new Request());
        $this->contextConfigurator->setRequest(null);
    }
}
