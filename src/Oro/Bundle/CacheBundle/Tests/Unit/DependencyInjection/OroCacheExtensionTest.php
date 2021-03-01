<?php
declare(strict_types=1);

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CacheBundle\DependencyInjection\OroCacheExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class OroCacheExtensionTest extends ExtensionTestCase
{
    protected function buildContainerMock(): ContainerBuilder
    {
        $containerBuilder = parent::buildContainerMock();

        $warmerServiceDefinition = $this->createMock(Definition::class);
        $warmerServiceDefinition->expects(static::once())
            ->method('replaceArgument')
            ->with(0, static::isType('array'));
        $containerBuilder
            ->expects(static::once())
            ->method('getDefinition')
            ->with('oro.cache.serializer.mapping.cache_warmer')
            ->willReturn($warmerServiceDefinition);

        return $containerBuilder;
    }

    public function testLoad(): void
    {
        $this->loadExtension(new OroCacheExtension());

        $expectedDefinitions = [
            'oro_cache.action.handler.invalidate_scheduled',
            'oro_cache.action.handler.schedule_arguments_builder',
            'oro_cache.action.provider.invalidate_cache_time',
            'oro_cache.action.transformer.datetime_to_cron_format',
            'oro.cache.serializer.mapping.cache_warmer',
            'oro.cache.serializer.mapping.factory.class_metadata',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
