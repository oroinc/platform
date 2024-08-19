<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\SyncProcessingCompilerPass;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SyncProcessingCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        DependencyInjectionUtil::setConfig($container, [
            'batch_api' => [
                'sync_processing_wait_timeout'                   => 50,
                'sync_processing_limit'                          => 10,
                'sync_processing_limit_per_entity'               => [
                    'Test\Entity1' => 11
                ],
                'sync_processing_included_data_limit'            => 20,
                'sync_processing_included_data_limit_per_entity' => [
                    'Test\Entity1' => 21
                ]
            ]
        ]);
        $processSyncOperationProcessorDef = $container->register('oro_api.update_list.process_synchronous_operation')
            ->addArgument(new Reference('oro_api.doctrine_helper'))
            ->setArgument('$waitTimeout', new AbstractArgument());
        $syncProcessingLimitProviderDef = $container->register('oro_api.batch.sync_processing_limit_provider')
            ->setArgument('$defaultLimit', new AbstractArgument())
            ->setArgument('$entityLimits', new AbstractArgument())
            ->setArgument('$defaultIncludedDataLimit', new AbstractArgument())
            ->setArgument('$entityIncludedDataLimits', new AbstractArgument());

        $compiler = new SyncProcessingCompilerPass();
        $compiler->process($container);

        self::assertEquals(
            [
                0              => new Reference('oro_api.doctrine_helper'),
                '$waitTimeout' => 50
            ],
            $processSyncOperationProcessorDef->getArguments()
        );
        self::assertEquals(
            [
                '$defaultLimit'             => 10,
                '$entityLimits'             => [
                    'Test\Entity1' => 11
                ],
                '$defaultIncludedDataLimit' => 20,
                '$entityIncludedDataLimits' => [
                    'Test\Entity1' => 21
                ]
            ],
            $syncProcessingLimitProviderDef->getArguments()
        );
    }
}
