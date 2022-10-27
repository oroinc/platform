<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LayoutBundle\Cache\TwigTemplateCacheWarmer;
use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\TwigTemplateCacheWarmerPass;
use Symfony\Bundle\TwigBundle\CacheWarmer\TemplateCacheWarmer;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TwigTemplateCacheWarmerPassTest extends \PHPUnit\Framework\TestCase
{
    private TwigTemplateCacheWarmerPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new TwigTemplateCacheWarmerPass();
    }

    public function testTwigCacheDisabled()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);

        $this->assertFalse($container->hasDefinition('twig.template_cache_warmer'));
    }

    public function testConfigureTwigCacheWarmer()
    {
        $container = new ContainerBuilder();
        $cacheWarmerDef = $container->register('twig.template_cache_warmer', TemplateCacheWarmer::class);

        $this->compiler->process($container);

        $this->assertEquals(TwigTemplateCacheWarmer::class, $cacheWarmerDef->getClass());
    }
}
