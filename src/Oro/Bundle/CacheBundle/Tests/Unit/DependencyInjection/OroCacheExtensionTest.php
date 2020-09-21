<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CacheBundle\DependencyInjection\OroCacheExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class OroCacheExtensionTest extends ExtensionTestCase
{
    /**
     * @var OroCacheExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new OroCacheExtension();
    }

    /**
     * {@inheritdoc}
     */
    protected function buildContainerMock()
    {
        $mockBuilder = $this->createMock(ContainerBuilder::class);

        $warmerServiceDefinition = $this->createMock(Definition::class);
        $warmerServiceDefinition->expects($this->once())
            ->method('replaceArgument')
            ->with(0, $this->isType('array'));
        $mockBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with('oro.cache.serializer.mapping.cache_warmer')
            ->willReturn($warmerServiceDefinition);

        return $mockBuilder;
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

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
