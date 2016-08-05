<?php
namespace Oro\Component\MessageQueue\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DefaultTransportFactory implements TransportFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'default')
    {
        $this->name = $name;
    }
    
    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        $builder
            ->beforeNormalization()
                ->ifString()
                ->then(function ($v) {
                    return array('alias' => $v);
                })
            ->end()
            ->children()
                ->scalarNode('alias')->isRequired()->cannotBeEmpty()->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createService(ContainerBuilder $container, array $config)
    {
        $connectionId = sprintf('oro_message_queue.transport.%s.connection', $this->getName());
        $aliasId = sprintf('oro_message_queue.transport.%s.connection', $config['alias']);
        
        $container->setAlias($connectionId, $aliasId);
        $container->setAlias('oro_message_queue.transport.connection', $connectionId);

        return $connectionId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
