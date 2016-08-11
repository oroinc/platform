<?php
namespace Oro\Component\MessageQueue\DependencyInjection;

use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RedeliverOrphanMessagesDbalExtension;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RejectMessageOnExceptionDbalExtension;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DbalTransportFactory implements TransportFactoryInterface
{
    /**
     * @var string
     */
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
        $builder
            ->children()
                ->scalarNode('connection')->defaultValue('default')->cannotBeEmpty()->end()
                ->scalarNode('table')->defaultValue('oro_message_queue')->cannotBeEmpty()->end()
                ->integerNode('orphan_time')->min(30)->defaultValue(300)->cannotBeEmpty()->end()
                ->integerNode('polling_interval')->min(50)->defaultValue(1000)->cannotBeEmpty()->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createService(ContainerBuilder $container, array $config)
    {
        $orphanExtension = new Definition(RedeliverOrphanMessagesDbalExtension::class);
        $orphanExtension->setPublic(false);
        $orphanExtension->addTag('oro_message_queue.consumption.extension', ['priority' => 20]);
        $orphanExtension->setArguments([
            $config['orphan_time'],
        ]);
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

        $dbalConnection = new Definition(Connection::class);
        $dbalConnection->setPublic(false);
        $dbalConnection->setFactory([new Reference('doctrine'), 'getConnection']);
        $dbalConnection->setArguments([$config['connection']]);

        $dbalConnectionId = sprintf('oro_message_queue.transport.%s.dbal_connection', $this->name);
        $container->setDefinition($dbalConnectionId, $dbalConnection);

        $options = [
            'polling_interval' => $config['polling_interval'],
        ];

        $connection = new Definition(DbalConnection::class);
        $connection->setArguments([new Reference($dbalConnectionId), $config['table'], $options]);

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
