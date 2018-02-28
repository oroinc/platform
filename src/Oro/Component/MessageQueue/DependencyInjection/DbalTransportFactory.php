<?php

namespace Oro\Component\MessageQueue\DependencyInjection;

use Oro\Component\MessageQueue\Consumption\Dbal\DbalCliProcessManager;
use Oro\Component\MessageQueue\Consumption\Dbal\DbalPidFileManager;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RedeliverOrphanMessagesDbalExtension;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RejectMessageOnExceptionDbalExtension;
use Oro\Component\MessageQueue\Transport\Dbal\DbalLazyConnection;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DbalTransportFactory implements TransportFactoryInterface
{
    /** @var string */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'dbal')
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        $pidFileDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'oro-message-queue';

        $builder
            ->children()
                ->scalarNode('connection')->defaultValue('default')->cannotBeEmpty()->end()
                ->scalarNode('table')->defaultValue('oro_message_queue')->cannotBeEmpty()->end()
                ->scalarNode('pid_file_dir')->defaultValue($pidFileDir)->cannotBeEmpty()->end()
                ->integerNode('consumer_process_pattern')
                    ->defaultValue(':consume')
                    ->cannotBeEmpty()
                    ->end()
                ->integerNode('polling_interval')->min(50)->defaultValue(1000)->cannotBeEmpty()->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createService(ContainerBuilder $container, array $config)
    {
        $container->setParameter('oro_message_queue.dbal.pid_file_dir', $config['pid_file_dir']);

        $pidFileManager = new Definition(DbalPidFileManager::class, [$config['pid_file_dir']]);
        $pidFileManager->setPublic(false);
        $pidFileManagerId = sprintf('oro_message_queue.consumption.%s.pid_file_manager', $this->name);
        $container->setDefinition($pidFileManagerId, $pidFileManager);

        $cliProcessManager = new Definition(DbalCliProcessManager::class);
        $cliProcessManager->setPublic(false);
        $cliProcessManagerId = sprintf('oro_message_queue.consumption.%s.cli_process_manager', $this->name);
        $container->setDefinition($cliProcessManagerId, $cliProcessManager);

        $orphanExtension = new Definition(RedeliverOrphanMessagesDbalExtension::class, [
            new Reference($pidFileManagerId),
            new Reference($cliProcessManagerId),
            $config['consumer_process_pattern']
        ]);
        $orphanExtension->setPublic(false);
        $orphanExtension->addTag('oro_message_queue.consumption.extension', ['priority' => -20]);
        $container->setDefinition(
            sprintf('oro_message_queue.consumption.%s.redeliver_orphan_messages_extension', $this->name),
            $orphanExtension
        );

        $rejectOnExceptionExtension = new Definition(RejectMessageOnExceptionDbalExtension::class);
        $rejectOnExceptionExtension->setPublic(false);
        $rejectOnExceptionExtension->addTag('oro_message_queue.consumption.extension');
        $container->setDefinition(
            sprintf('oro_message_queue.consumption.%s.reject_message_on_exception_extension', $this->name),
            $rejectOnExceptionExtension
        );

        $connection = new Definition(DbalLazyConnection::class, [
            new Reference('doctrine'),
            $config['connection'],
            $config['table'],
            ['polling_interval' => $config['polling_interval']]
        ]);
        $connectionId = sprintf('oro_message_queue.transport.%s.connection', $this->getName());
        $container->setDefinition($connectionId, $connection);

        return $connectionId;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }
}
