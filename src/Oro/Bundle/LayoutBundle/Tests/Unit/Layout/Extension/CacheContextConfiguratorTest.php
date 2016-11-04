<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Doctrine\Common\Cache\Cache;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\LayoutBundle\Layout\Extension\CacheContextConfigurator;
use Oro\Bundle\LayoutBundle\Layout\Extension\ActionContextConfigurator;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ThemeResourceProvider;

class CacheContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActionContextConfigurator */
    protected $contextConfigurator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Cache
     */
    protected $cache;

    protected function setUp()
    {
        $this->cache = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->contextConfigurator = new CacheContextConfigurator($this->cache);
    }

    public function testConfigureContextWithDefaultAction()
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertSame('', $context[CacheContextConfigurator::MAX_MODIFICATION_DATE_PARAM]);
    }

    public function testConfigureContext()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $context = new LayoutContext();

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(ThemeResourceProvider::CACHE_LAST_MODIFICATION_DATE)
            ->willReturn($now);

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals(
            $now->format(\DateTime::COOKIE),
            $context[CacheContextConfigurator::MAX_MODIFICATION_DATE_PARAM]
        );
    }
}
