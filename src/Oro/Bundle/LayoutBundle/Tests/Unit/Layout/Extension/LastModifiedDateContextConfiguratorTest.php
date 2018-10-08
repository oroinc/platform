<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\LayoutBundle\Layout\Extension\ActionContextConfigurator;
use Oro\Bundle\LayoutBundle\Layout\Extension\LastModifiedDateContextConfigurator;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ThemeResourceProvider;
use Oro\Component\Layout\LayoutContext;

class LastModifiedDateContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActionContextConfigurator */
    protected $contextConfigurator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Cache
     */
    protected $cache;

    protected function setUp()
    {
        $this->cache = $this->createMock('Doctrine\Common\Cache\Cache');
        $this->contextConfigurator = new LastModifiedDateContextConfigurator($this->cache);
    }

    public function testConfigureContextWithDefaultAction()
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertTrue(is_string($context[LastModifiedDateContextConfigurator::MAX_MODIFICATION_DATE_PARAM]));
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
            $context[LastModifiedDateContextConfigurator::MAX_MODIFICATION_DATE_PARAM]
        );
    }
}
