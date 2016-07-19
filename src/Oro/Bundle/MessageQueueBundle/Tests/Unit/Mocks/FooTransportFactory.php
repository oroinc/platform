<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Mocks;

use Oro\Component\MessageQueue\DependencyInjection\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class FooTransportFactory implements TransportFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'foo')
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
                ->scalarNode('foo_param')->isRequired()->cannotBeEmpty()->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createService(ContainerBuilder $container, array $config)
    {
        $connectionId = 'foo.connection';
        
        $container->setDefinition($connectionId, new Definition(\stdClass::class, [$config]));
        
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