<?php
declare(strict_types=1);

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CacheBundle\DependencyInjection\OroCacheExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\LoaderChain;

class OroCacheExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $extension = new OroCacheExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());

        self::assertTrue($container->hasDefinition('oro.cache.serializer.mapping.cache_warmer'));
        self::assertEquals(
            new Definition(ClassMetadataFactory::class, [new Definition(LoaderChain::class, [[]])]),
            $container->getDefinition('oro.cache.serializer.mapping.factory.class_metadata')
        );
    }
}
