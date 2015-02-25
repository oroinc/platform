<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\UIBundle\Provider\UserAgent;
use Symfony\Component\HttpFoundation\Request;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\UIBundle\Layout\Extension\UserAgentContextConfigurator;

class UserAgentContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var UserAgentContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->contextConfigurator = new UserAgentContextConfigurator();
    }

    public function testConfigureContext()
    {
        $request = new Request();
        $request->headers->set(
            'User-Agent',
            'Mozilla/5.0 (Linux; U; Android 2.3; en-us) AppleWebKit/999+ (KHTML, like Gecko) Safari/999.9'
        );

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $this->contextConfigurator->setRequest($request);
        $context->resolve();

        $this->assertFalse($context['user_agent']['desktop']);
    }

    public function testConfigureContextOverride()
    {
        $request = new Request();
        $request->headers->set(
            'User-Agent',
            'Mozilla/5.0 (Linux; U; Android 2.3; en-us) AppleWebKit/999+ (KHTML, like Gecko) Safari/999.9'
        );

        $context = new LayoutContext();
        $context['user_agent'] = new UserAgent(
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; DigExt)'
        );

        $this->contextConfigurator->configureContext($context);
        $this->contextConfigurator->setRequest($request);
        $context->resolve();

        $this->assertTrue($context['user_agent']['desktop']);
    }

    public function testConfigureContextWithoutRequest()
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertTrue($context['user_agent']['desktop']);
    }
}
