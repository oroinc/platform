<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ChunkSizeProviderCompilerPass;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ChunkSizeProviderCompilerPassTest extends TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        DependencyInjectionUtil::setConfig($container, [
            'batch_api' => [
                'chunk_size'                          => 10,
                'chunk_size_per_entity'               => [
                    'Test\Entity1' => 11
                ],
                'included_data_chunk_size'            => 20,
                'included_data_chunk_size_per_entity' => [
                    'Test\Entity1' => 21
                ]
            ]
        ]);
        $chunkSizeProviderDef = $container->register('oro_api.batch.chunk_size_provider')
            ->setArgument('$defaultChunkSize', new AbstractArgument())
            ->setArgument('$entityChunkSizes', new AbstractArgument())
            ->setArgument('$defaultIncludedDataChunkSize', new AbstractArgument())
            ->setArgument('$entityIncludedDataChunkSizes', new AbstractArgument());

        $compiler = new ChunkSizeProviderCompilerPass();
        $compiler->process($container);

        self::assertEquals(
            [
                '$defaultChunkSize'             => 10,
                '$entityChunkSizes'             => [
                    'Test\Entity1' => 11
                ],
                '$defaultIncludedDataChunkSize' => 20,
                '$entityIncludedDataChunkSizes' => [
                    'Test\Entity1' => 21
                ]
            ],
            $chunkSizeProviderDef->getArguments()
        );
    }
}
