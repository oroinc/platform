<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Configuration;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory\DbalTransportFactory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $factories = [];
        $configuration = new Configuration($factories);

        $treeBuilder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
    }

    public function testProcessConfiguration(): void
    {
        $factories = [
            'dbal' => new DbalTransportFactory(),
        ];

        $configuration = new Configuration($factories);
        $processor = new Processor();

        $expected = [
            'persistent_services' => [],
            'persistent_processors' => [],
            'security_agnostic_topics' => [],
            'security_agnostic_processors' => [],
            'time_before_stale' => [
                'jobs' => []
            ],
            'consumer' => [
                'heartbeat_update_period' => 15
            ],
            'transport' => [
                'dbal' => [
                    'connection' => 'message_queue',
                    'table' => 'oro_message_queue',
                    'pid_file_dir' => '/tmp/oro-message-queue',
                    'consumer_process_pattern' => ':consume',
                    'polling_interval' => 1000,
                ],
            ],
            'client' => [
                'traceable_producer' => false,
                'prefix' => 'oro',
                'router_processor' => 'oro_message_queue.client.route_message_processor',
                'router_destination' => 'default',
                'default_destination' => 'default',
                'default_topic' => 'default',
                'redelivery' => [
                    'enabled' => true,
                    'delay_time' => 10,
                ],
            ]
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, [
            'oro_message_queue' => [
                'persistent_services' => [],
                'persistent_processors' => [],
                'security_agnostic_topics' => [],
                'security_agnostic_processors' => [],
                'time_before_stale' => [],
                'consumer' => [],
                'transport' => [],
                'client' => [],
            ]

        ]));
    }
}
