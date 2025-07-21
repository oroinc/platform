<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\XmlHttpRequestConfigurator;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class XmlHttpRequestConfiguratorTest extends TestCase
{
    private RequestStack $requestStack;
    private XmlHttpRequestConfigurator $configurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();

        $this->configurator = new XmlHttpRequestConfigurator($this->requestStack);
    }

    public function testConfigureContextWithRequestAndNonSupportedRoute(): void
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->attributes->set('_route', 'route');

        $this->requestStack->push($request);
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Undefined index: is_xml_http_request.');
        $context->get('is_xml_http_request');
    }

    public function testConfigureContextWithXmlHttpRequest(): void
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->attributes->set('_route', 'route');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $this->requestStack->push($request);
        $this->configurator->addRoute('route');
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertTrue($context->get('is_xml_http_request'));
    }

    public function testConfigureContextWithRequest(): void
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->attributes->set('_route', 'route');

        $this->requestStack->push($request);
        $this->configurator->addRoute('route');
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertFalse($context->get('is_xml_http_request'));
    }

    public function testConfigureContextWithSubRequest(): void
    {
        $context = new LayoutContext();

        $request = Request::create('');
        $request->attributes->set('_master_request_route', 'route');

        $this->requestStack->push($request);
        $this->configurator->setRoutes(['route']);
        $this->configurator->configureContext($context);

        $context->resolve();
        $this->assertFalse($context->get('is_xml_http_request'));
    }
}
