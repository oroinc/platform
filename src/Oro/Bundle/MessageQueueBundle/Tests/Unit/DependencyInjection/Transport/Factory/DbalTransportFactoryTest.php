<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Transport\Factory;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory\DbalTransportFactory;
use Oro\Component\MessageQueue\Consumption\Dbal\DbalCliProcessManager;
use Oro\Component\MessageQueue\Consumption\Dbal\DbalPidFileManager;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RedeliverOrphanMessagesDbalExtension;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RejectMessageOnExceptionDbalExtension;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DbalTransportFactoryTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private DbalTransportFactory $dbalTransportFactory;

    protected function setUp(): void
    {
        $this->dbalTransportFactory = new DbalTransportFactory();
    }

    public function testCreate(): void
    {
        $container = new ContainerBuilder();
        $pidFileDir = $this->getTempDir('oro-message-queue');
        $config = [
            'connection' => 'message_queue',
            'table' => 'oro_message_queue',
            'pid_file_dir' => $pidFileDir,
            'consumer_process_pattern' => ':consume',
            'polling_interval' => 1000,
        ];

        $this->dbalTransportFactory->create($container, $config);
        self::assertEquals($pidFileDir, $container->getParameter('oro_message_queue.dbal.pid_file_dir'));
        self::assertEquals($config['connection'], $container->getParameter('oro_message_queue.dbal.connection'));
        self::assertEquals($config['table'], $container->getParameter('oro_message_queue.dbal.table'));
        self::assertEquals(
            ['polling_interval' => $config['polling_interval']],
            $container->getParameter('oro_message_queue.dbal.options')
        );

        self::assertEquals(
            DbalPidFileManager::class,
            $container->getDefinition('oro_message_queue.consumption.dbal.pid_file_manager')->getClass()
        );

        $cliProcessManagerDef = $container->getDefinition('oro_message_queue.consumption.dbal.cli_process_manager');
        self::assertEquals(DbalCliProcessManager::class, $cliProcessManagerDef->getClass());
        self::assertEquals([['setLogger', [new Reference('logger')]]], $cliProcessManagerDef->getMethodCalls());
        self::assertEquals([['channel' => 'consumer']], $cliProcessManagerDef->getTag('monolog.logger'));

        self::assertEquals(
            RedeliverOrphanMessagesDbalExtension::class,
            $container->getDefinition('oro_message_queue.consumption.dbal.redeliver_orphan_messages_extension')
                ->getClass()
        );
        self::assertEquals(
            ['oro_message_queue.consumption.extension' => [['priority' => -20]]],
            $container->getDefinition('oro_message_queue.consumption.dbal.redeliver_orphan_messages_extension')
                ->getTags()
        );

        self::assertEquals(
            RejectMessageOnExceptionDbalExtension::class,
            $container->getDefinition('oro_message_queue.consumption.dbal.reject_message_on_exception_extension')
                ->getClass()
        );
        self::assertEquals(
            ['oro_message_queue.consumption.extension' => [[]]],
            $container->getDefinition('oro_message_queue.consumption.dbal.reject_message_on_exception_extension')
                ->getTags()
        );
    }

    public function testGetKey(): void
    {
        self::assertEquals('dbal', $this->dbalTransportFactory->getKey());
    }

    public function testAddConfiguration(): void
    {
        $builder = new ArrayNodeDefinition('transport');
        $pidFileDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'oro-message-queue';

        $this->dbalTransportFactory->addConfiguration($builder);

        $expectedBuilder = new ArrayNodeDefinition('transport');
        $expectedBuilder->children()
            ->arrayNode('dbal')
                ->addDefaultsIfNotSet()
                ->info('DBAL transport configuration.')
                ->children()
                    ->scalarNode('connection')
                        ->defaultValue('message_queue')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('table')
                        ->defaultValue('oro_message_queue')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('pid_file_dir')
                        ->defaultValue($pidFileDir)
                        ->cannotBeEmpty()
                    ->end()
                    ->integerNode('consumer_process_pattern')
                        ->defaultValue(':consume')
                    ->end()
                    ->integerNode('polling_interval')
                        ->min(50)
                        ->defaultValue(1000)
                    ->end()
                ->end()
            ->end()
        ->end();

        self::assertEquals($expectedBuilder, $builder);
    }
}
