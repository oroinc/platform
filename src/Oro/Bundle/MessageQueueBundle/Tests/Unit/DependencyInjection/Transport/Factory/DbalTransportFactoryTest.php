<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Transport\Factory;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory\DbalTransportFactory;
use Oro\Component\MessageQueue\Consumption\Dbal\DbalCliProcessManager;
use Oro\Component\MessageQueue\Consumption\Dbal\DbalPidFileManager;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RedeliverOrphanMessagesDbalExtension;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RejectMessageOnExceptionDbalExtension;
use Oro\Component\MessageQueue\Transport\Dbal\DbalLazyConnection;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DbalTransportFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var DbalTransportFactory */
    private $dbalTransportFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->dbalTransportFactory = new DbalTransportFactory();
    }

    public function testCreate()
    {
        $container = new ContainerBuilder();
        $config = [
            'connection' => 'message_queue',
            'table' => 'oro_message_queue',
            'pid_file_dir' => '/tmp/oro-message-queue',
            'consumer_process_pattern' => ':consume',
            'polling_interval' => 1000,
        ];

        $connectionId = $this->dbalTransportFactory->create($container, $config);
        $this->assertEquals('oro_message_queue.transport.dbal.connection', $connectionId);
        $this->assertEquals('/tmp/oro-message-queue', $container->getParameter('oro_message_queue.dbal.pid_file_dir'));

        $this->assertEquals(
            DbalPidFileManager::class,
            $container->getDefinition('oro_message_queue.consumption.dbal.pid_file_manager')->getClass()
        );
        $this->assertFalse(
            $container->getDefinition('oro_message_queue.consumption.dbal.pid_file_manager')->isPublic()
        );

        $this->assertEquals(
            DbalCliProcessManager::class,
            $container->getDefinition('oro_message_queue.consumption.dbal.cli_process_manager')->getClass()
        );
        $this->assertFalse(
            $container->getDefinition('oro_message_queue.consumption.dbal.cli_process_manager')->isPublic()
        );

        $this->assertEquals(
            RedeliverOrphanMessagesDbalExtension::class,
            $container->getDefinition('oro_message_queue.consumption.dbal.redeliver_orphan_messages_extension')
                ->getClass()
        );
        $this->assertEquals(
            ['oro_message_queue.consumption.extension' => [['priority' => -20]]],
            $container->getDefinition('oro_message_queue.consumption.dbal.redeliver_orphan_messages_extension')
                ->getTags()
        );
        $this->assertFalse(
            $container->getDefinition('oro_message_queue.consumption.dbal.redeliver_orphan_messages_extension')
                ->isPublic()
        );

        $this->assertEquals(
            RejectMessageOnExceptionDbalExtension::class,
            $container->getDefinition('oro_message_queue.consumption.dbal.reject_message_on_exception_extension')
                ->getClass()
        );
        $this->assertEquals(
            ['oro_message_queue.consumption.extension' => [[]]],
            $container->getDefinition('oro_message_queue.consumption.dbal.reject_message_on_exception_extension')
                ->getTags()
        );
        $this->assertFalse(
            $container->getDefinition('oro_message_queue.consumption.dbal.reject_message_on_exception_extension')
                ->isPublic()
        );

        $this->assertEquals(
            DbalLazyConnection::class,
            $container->getDefinition('oro_message_queue.transport.dbal.connection')->getClass()
        );
    }

    public function testGetKey()
    {
        $this->assertEquals('dbal', $this->dbalTransportFactory->getKey());
    }

    public function testAddConfiguration()
    {
        $builder = new ArrayNodeDefinition('transport');

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
                        ->defaultValue('/tmp/oro-message-queue')
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

        $this->assertEquals($expectedBuilder, $builder);
    }
}
