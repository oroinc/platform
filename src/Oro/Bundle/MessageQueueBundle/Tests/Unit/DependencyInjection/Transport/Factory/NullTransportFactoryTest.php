<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Transport\Factory;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory\NullTransportFactory;
use Oro\Component\MessageQueue\Transport\Null\NullConnection;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NullTransportFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var NullTransportFactory */
    private $nullTransportFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->nullTransportFactory = new NullTransportFactory();
    }

    public function testCreate()
    {
        $container = new ContainerBuilder();
        $config = [];

        $connectionId = $this->nullTransportFactory->create($container, $config);
        $this->assertEquals('oro_message_queue.transport.null.connection', $connectionId);
        $this->assertEquals(
            NullConnection::class,
            $container->getDefinition('oro_message_queue.transport.null.connection')->getClass()
        );
    }

    public function testGetKey()
    {
        $this->assertEquals('null', $this->nullTransportFactory->getKey());
    }

    public function testAddConfiguration()
    {
        $builder = new ArrayNodeDefinition('transport');

        $this->nullTransportFactory->addConfiguration($builder);

        $expectedBuilder = new ArrayNodeDefinition('transport');
        $expectedBuilder->children()
            ->arrayNode('null')
                ->addDefaultsIfNotSet()
                ->info('NULL transport configuration.')
            ->end()
        ->end();

        $this->assertEquals($expectedBuilder, $builder);
    }
}
