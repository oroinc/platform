<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\LazyServicesCompilerPass;
use Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Fixtures;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LazyServicesCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldMarkServicesAsLazy()
    {
        $fooBundle = new Fixtures\FooBundle\FooBundle();
        $barBundle = new Fixtures\BarBundle\BarBundle();

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $fooBundle->getName() => get_class($fooBundle),
                $barBundle->getName() => get_class($barBundle)
            ]);

        $container = new ContainerBuilder();
        $container->register('foo_service');
        $container->register('bar_service');

        $compiler = new LazyServicesCompilerPass();
        $compiler->process($container);

        self::assertTrue($container->getDefinition('foo_service')->isLazy());
        self::assertTrue($container->getDefinition('bar_service')->isLazy());
        self::assertFalse($container->hasDefinition('not_existing_service'));
    }
}
