<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\UIBundle\Layout\Extension\HashNavContextConfigurator;

class HashNavContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var HashNavContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->contextConfigurator = new HashNavContextConfigurator();
    }

    public function testConfigureContextWhenNoHashNavRequest()
    {
        $request = new Request();
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $this->contextConfigurator->setRequest($request);
        $context->resolve();

        $this->assertFalse($context['hash_navigation']);
    }

    public function testConfigureContextWithFalseHashNavHeader()
    {
        $request = new Request();
        $request->headers->set(HashNavContextConfigurator::HASH_NAVIGATION_HEADER, false);

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $this->contextConfigurator->setRequest($request);
        $context->resolve();

        $this->assertFalse($context['hash_navigation']);
    }

    public function testConfigureContextWithFalseHashNavParam()
    {
        $request = new Request();
        $request->query->set(HashNavContextConfigurator::HASH_NAVIGATION_HEADER, false);

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $this->contextConfigurator->setRequest($request);
        $context->resolve();

        $this->assertFalse($context['hash_navigation']);
    }

    public function testConfigureContextWithHashNavHeader()
    {
        $request = new Request();
        $request->headers->set(HashNavContextConfigurator::HASH_NAVIGATION_HEADER, true);

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $this->contextConfigurator->setRequest($request);
        $context->resolve();

        $this->assertTrue($context['hash_navigation']);
    }

    public function testConfigureContextWithHashNavParam()
    {
        $request = new Request();
        $request->query->set(HashNavContextConfigurator::HASH_NAVIGATION_HEADER, true);

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $this->contextConfigurator->setRequest($request);
        $context->resolve();

        $this->assertTrue($context['hash_navigation']);
    }

    public function testConfigureContextOverride()
    {
        $request = new Request();
        $request->headers->set(HashNavContextConfigurator::HASH_NAVIGATION_HEADER, true);

        $context                    = new LayoutContext();
        $context['hash_navigation'] = false;

        $this->contextConfigurator->configureContext($context);
        $this->contextConfigurator->setRequest($request);
        $context->resolve();

        $this->assertFalse($context['hash_navigation']);
    }

    public function testConfigureContextWithoutRequest()
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse($context['hash_navigation']);
    }
}
