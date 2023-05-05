<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MessageQueueBundle\Command\CleanupCommand;
use Oro\Bundle\MessageQueueBundle\Command\ClientConsumeMessagesCommand;
use Oro\Bundle\MessageQueueBundle\Command\ConsumerHeartbeatCommand;
use Oro\Bundle\MessageQueueBundle\Command\TransportConsumeMessagesCommand;
use Oro\Bundle\MessageQueueBundle\Controller\Api\Rest\JobController as ApiRestJobController;
use Oro\Bundle\MessageQueueBundle\Controller\JobController;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\OroMessageQueueExtension;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory\DbalTransportFactory;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\Environment\TestBufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\Environment\TestMessageBufferManager;
use Oro\Component\MessageQueue\Client\CreateQueuesCommand;
use Oro\Component\MessageQueue\Client\DbalDriver;
use Oro\Component\MessageQueue\Client\Meta\DestinationsCommand;
use Oro\Component\MessageQueue\Client\Meta\TopicsCommand;
use Oro\Component\MessageQueue\Client\NoopMessageProcessor;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalLazyConnection;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class OroMessageQueueExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = $this->getContainer(['kernel.environment' => 'prod']);
        $container->addDefinitions(
            [
                'oro_message_queue.client.driver_factory' => new Definition(\stdClass::class, [[]]),
                'oro_message_queue.client.config' => new Definition(),
                'oro_message_queue.consumption.redelivery_message_extension' =>
                    new Definition(\stdClass::class, ['', 0]),
                'oro_message_queue.consumption.signal_extension' => new Definition(),
            ]
        );

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new DbalTransportFactory());
        $extension->load([], $container);

        self::assertParametersExist($this->getRequiredParameters(), $container);
        self::assertDefinitionsExist($this->getRequiredDefinitions(), $container);
        self::assertEquals(
            [
                DbalConnection::class => DbalDriver::class,
                DbalLazyConnection::class => DbalDriver::class,
            ],
            $container->getDefinition('oro_message_queue.client.driver_factory')->getArgument(0)
        );
        self::assertEquals(
            ['oro', 'default', 'default'],
            $container->getDefinition('oro_message_queue.client.config')->getArguments()
        );

        $redeliveryExtension = $container->getDefinition('oro_message_queue.consumption.redelivery_message_extension');
        self::assertEquals(10, $redeliveryExtension->getArgument(1));
        self::assertEquals(
            ['oro_message_queue.consumption.extension' => [['priority' => 512]]],
            $redeliveryExtension->getTags()
        );
        self::assertEquals(
            ['oro_message_queue.consumption.extension' => [['persistent' => true]]],
            $container->getDefinition('oro_message_queue.consumption.signal_extension')->getTags()
        );

        $autoconfiguration = $container->getAutoconfiguredInstanceof();
        self::assertArrayHasKey(TopicInterface::class, $autoconfiguration);
        self::assertEquals(
            (new ChildDefinition(''))->addTag('oro_message_queue.topic'),
            $autoconfiguration[TopicInterface::class]
        );
    }

    private static function assertParametersExist(array $parameters, ContainerBuilder $container): void
    {
        self::assertEmpty(array_diff($parameters, array_keys($container->getParameterBag()->all())));
    }

    private static function assertDefinitionsExist(array $definitionsKeys, ContainerBuilder $container): void
    {
        self::assertEmpty(array_diff($definitionsKeys, array_keys($container->getDefinitions())));
    }

    public function testLoadWithDisabledRedelivery(): void
    {
        $configs = [
            'oro_message_queue' => [
                'client' => [
                    'redelivery' => [
                        'enabled' => false,
                    ],
                ],
            ],
        ];

        $container = $this->getContainer(['kernel.environment' => 'prod']);
        $container->addDefinitions(
            [
                'oro_message_queue.client.driver_factory' => new Definition(\stdClass::class, [[]]),
                'oro_message_queue.client.config' => new Definition(),
                'oro_message_queue.consumption.signal_extension' => new Definition(),
            ]
        );

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new DbalTransportFactory());
        $extension->load($configs, $container);

        self::assertParametersExist($this->getRequiredParameters(), $container);
        self::assertDefinitionsExist($this->getRequiredDefinitions(), $container);
        self::assertEquals(
            [
                DbalConnection::class => DbalDriver::class,
                DbalLazyConnection::class => DbalDriver::class,
            ],
            $container->getDefinition('oro_message_queue.client.driver_factory')->getArgument(0)
        );
        self::assertEquals(
            ['oro', 'default', 'default'],
            $container->getDefinition('oro_message_queue.client.config')->getArguments()
        );
        self::assertEquals(
            ['oro_message_queue.consumption.extension' => [['persistent' => true]]],
            $container->getDefinition('oro_message_queue.consumption.signal_extension')->getTags()
        );
    }

    public function testLoadWithClientConfig(): void
    {
        $configs = [
            'oro_message_queue' => [
                'client' => [
                    'prefix' => 'test_',
                    'default_destination' => 'default.destination',
                    'default_topic' => 'default.topic',
                    'noop_status' => NoopMessageProcessor::REJECT,
                ],
            ],
        ];

        $container = $this->getContainer(['kernel.environment' => 'prod']);
        $container->addDefinitions(
            [
                'oro_message_queue.client.driver_factory' => new Definition(\stdClass::class, [[]]),
                'oro_message_queue.client.config' => new Definition(),
                'oro_message_queue.consumption.redelivery_message_extension' =>
                    new Definition(\stdClass::class, ['', 0]),
                'oro_message_queue.consumption.signal_extension' => new Definition(),
            ]
        );

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new DbalTransportFactory());
        $extension->load($configs, $container);

        self::assertParametersExist($this->getRequiredParameters(), $container);
        self::assertDefinitionsExist($this->getRequiredDefinitions(), $container);
        self::assertEquals(
            NoopMessageProcessor::REJECT,
            $container->getParameter('oro_message_queue.client.noop_status')
        );
        self::assertEquals(
            [
                DbalConnection::class => DbalDriver::class,
                DbalLazyConnection::class => DbalDriver::class,
            ],
            $container->getDefinition('oro_message_queue.client.driver_factory')->getArgument(0)
        );
        self::assertEquals(
            ['test_', 'default.destination', 'default.topic'],
            $container->getDefinition('oro_message_queue.client.config')->getArguments()
        );

        $redeliveryExtension = $container->getDefinition('oro_message_queue.consumption.redelivery_message_extension');
        self::assertEquals(10, $redeliveryExtension->getArgument(1));
        self::assertEquals(
            ['oro_message_queue.consumption.extension' => [['priority' => 512]]],
            $redeliveryExtension->getTags()
        );
        self::assertEquals(
            ['oro_message_queue.consumption.extension' => [['persistent' => true]]],
            $container->getDefinition('oro_message_queue.consumption.signal_extension')->getTags()
        );
    }

    public function testLoadWithClientConfigAndTraceableProducer(): void
    {
        $configs = [
            'oro_message_queue' => [
                'client' => [
                    'prefix' => 'test_',
                    'default_destination' => 'default.destination',
                    'default_topic' => 'default.topic',
                    'redelivery' => ['delay_time' => 2119],
                    'traceable_producer' => true,
                ],
            ],
        ];

        $container = $this->getContainer(['kernel.environment' => 'prod']);
        $container->addDefinitions(
            [
                'oro_message_queue.client.driver_factory' => new Definition(\stdClass::class, [[]]),
                'oro_message_queue.client.config' => new Definition(),
                'oro_message_queue.consumption.redelivery_message_extension' =>
                    new Definition(\stdClass::class, ['', 0]),
                'oro_message_queue.consumption.signal_extension' => new Definition(),
            ]
        );

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new DbalTransportFactory());
        $extension->load($configs, $container);

        self::assertParametersExist($this->getRequiredParameters(), $container);
        self::assertDefinitionsExist(
            array_merge(
                $this->getRequiredDefinitions(),
                ['oro_message_queue.client.traceable_message_producer']
            ),
            $container
        );
        self::assertEquals(
            NoopMessageProcessor::REQUEUE,
            $container->getParameter('oro_message_queue.client.noop_status')
        );
        self::assertEquals(
            [
                DbalConnection::class => DbalDriver::class,
                DbalLazyConnection::class => DbalDriver::class,
            ],
            $container->getDefinition('oro_message_queue.client.driver_factory')->getArgument(0)
        );
        self::assertEquals(
            ['test_', 'default.destination', 'default.topic'],
            $container->getDefinition('oro_message_queue.client.config')->getArguments()
        );

        $redeliveryExtension = $container->getDefinition('oro_message_queue.consumption.redelivery_message_extension');
        self::assertEquals(2119, $redeliveryExtension->getArgument(1));
        self::assertEquals(
            ['oro_message_queue.consumption.extension' => [['priority' => 512]]],
            $redeliveryExtension->getTags()
        );
        self::assertEquals(
            ['oro_message_queue.consumption.extension' => [['persistent' => true]]],
            $container->getDefinition('oro_message_queue.consumption.signal_extension')->getTags()
        );
        self::assertEquals(
            '.inner',
            (string)$container->getDefinition('oro_message_queue.client.traceable_message_producer')->getArgument(0)
        );
    }

    public function testLoadWithConsumerConfig(): void
    {
        $configs = [
            'oro_message_queue' => [
                'consumer' => [
                    'heartbeat_update_period' => 1823,
                ],
            ],
        ];

        $container = $this->getContainer(['kernel.environment' => 'prod']);
        $container->addDefinitions(
            [
                'oro_message_queue.client.driver_factory' => new Definition(\stdClass::class, [[]]),
                'oro_message_queue.client.config' => new Definition(),
                'oro_message_queue.consumption.redelivery_message_extension' =>
                    new Definition(\stdClass::class, ['', 0]),
                'oro_message_queue.consumption.signal_extension' => new Definition(),
            ]
        );

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new DbalTransportFactory());
        $extension->load($configs, $container);

        self::assertParametersExist(
            array_merge(
                $this->getRequiredParameters(),
                ['oro_message_queue.consumer_heartbeat_update_period']
            ),
            $container
        );
        self::assertDefinitionsExist($this->getRequiredDefinitions(), $container);
        self::assertEquals(1823, $container->getParameter('oro_message_queue.consumer_heartbeat_update_period'));
        self::assertEmpty(array_diff($this->getRequiredDefinitions(), array_keys($container->getDefinitions())));
        self::assertEquals(
            [
                DbalConnection::class => DbalDriver::class,
                DbalLazyConnection::class => DbalDriver::class,
            ],
            $container->getDefinition('oro_message_queue.client.driver_factory')->getArgument(0)
        );
        self::assertEquals(
            ['oro', 'default', 'default'],
            $container->getDefinition('oro_message_queue.client.config')->getArguments()
        );
        self::assertEquals(
            ['oro_message_queue.consumption.extension' => [['persistent' => true]]],
            $container->getDefinition('oro_message_queue.consumption.signal_extension')->getTags()
        );
    }

    public function testLoadTestEnvironment(): void
    {
        $container = $this->getContainer(['kernel.environment' => 'test']);
        $container->addDefinitions(
            [
                'oro_message_queue.client.driver_factory' => new Definition(\stdClass::class, [[]]),
                'oro_message_queue.client.config' => new Definition(),
                'oro_message_queue.consumption.redelivery_message_extension' =>
                    new Definition(\stdClass::class, ['', 0]),
                'oro_message_queue.consumption.signal_extension' => new Definition(),
                'oro_message_queue.client.buffered_message_producer' => new Definition(),
                'oro_message_queue.client.message_buffer_manager' => new Definition(),
            ]
        );

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new DbalTransportFactory());
        $extension->load([], $container);

        self::assertParametersExist($this->getRequiredParameters(), $container);
        self::assertDefinitionsExist(
            array_merge($this->getRequiredDefinitions(), [
                'oro_message_queue.async.unique_message_processor',
                'oro_message_queue.async.dependent_message_processor',
            ]),
            $container
        );
        self::assertEquals(
            [
                DbalConnection::class => DbalDriver::class,
                DbalLazyConnection::class => DbalDriver::class,
            ],
            $container->getDefinition('oro_message_queue.client.driver_factory')->getArgument(0)
        );
        self::assertEquals(
            ['oro', 'default', 'default'],
            $container->getDefinition('oro_message_queue.client.config')->getArguments()
        );
        self::assertEquals(
            ['oro_message_queue.consumption.extension' => [['persistent' => true]]],
            $container->getDefinition('oro_message_queue.consumption.signal_extension')->getTags()
        );
        $bufferedMessageProducer = $container->getDefinition('oro_message_queue.client.buffered_message_producer');
        self::assertEquals(TestBufferedMessageProducer::class, $bufferedMessageProducer->getClass());
        self::assertTrue($bufferedMessageProducer->isPublic());
        self::assertEquals(
            TestMessageBufferManager::class,
            $container->getDefinition('oro_message_queue.client.message_buffer_manager')->getClass()
        );
    }

    private function getRequiredParameters(): array
    {
        return [
            'oro_message_queue.maintenance.idle_time',
            'oro_message_queue.job.unique_job_table_name',
            'oro_message_queue.client.noop_status',
            'oro_message_queue.dbal.pid_file_dir',
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getRequiredDefinitions(): array
    {
        return [
            'oro_message_queue.client.driver_factory',
            'oro_message_queue.client.config',
            'oro_message_queue.consumption.redelivery_message_extension',
            'oro_message_queue.consumption.signal_extension',
            'oro_message_queue.consumption.extensions',
            'oro_message_queue.consumption.maintenance_extension',
            'oro_message_queue.consumption.doctrine_clear_identity_map_extension',
            'oro_message_queue.consumption.database_connections_clear',
            'oro_message_queue.consumption.container_reset_extension',
            'oro_message_queue.consumption.interrupt_consumption_extension',
            'oro_message_queue.consumption.consumer_heartbeat_extension',
            'oro_message_queue.consumption.security_aware_extension',
            'oro_message_queue.consumption.locale_extension',
            'oro_message_queue.consumption.clear_logger_extension',
            'oro_message_queue.consumption.container_clearer',
            'oro_message_queue.consumption.garbage_collector_clearer',
            'oro_message_queue.consumption.queue_consumer',
            'oro_message_queue.listener.update_schema',
            'oro_message_queue.consumption.cache_state',
            'oro_message_queue.consumption.cache_state_driver.dbal',
            'oro_message_queue.consumption.consumer_heartbeat',
            'oro_message_queue.consumption.consumer_state_driver.dbal',
            'oro_message_queue.listener.authentication',
            'oro_message_queue.listener.console_fatal_error',
            'oro_message_queue.topic.message_queue_heartbeat',
            'oro_message_queue.cache.doctrine_metadata',
            'oro_message_queue.platform.optional_listener_extension',
            'oro_message_queue.log.consumer_state',
            'oro_message_queue.log.consumption_extension',
            'oro_message_queue.log.job_extension',
            'oro_message_queue.log.message_processor_class_provider',
            'oro_message_queue.log.message_to_array_converter',
            'oro_message_queue.log.message_to_array_converter.base',
            'oro_message_queue.log.processor.restore_original_channel',
            'oro_message_queue.log.processor.add_consumer_state',
            'oro_message_queue.log.handler.console',
            'oro_message_queue.log.handler.verbosity_filter',
            'oro_message_queue.log.handler.resend_job',
            'oro_message_queue.job.configuration_provider',
            'oro_message_queue.job.manager',
            'oro_message_queue.job.unique_job_handler',
            'oro_message_queue.job.processor',
            'oro_message_queue.job.runner',
            'oro_message_queue.job.extensions',
            'oro_message_queue.job.root_job_status_calculator',
            'oro_message_queue.checker.job_status_checker',
            'oro_message_queue.status_calculator.abstract_status_calculator',
            'oro_message_queue.status_calculator.collection_calculator',
            'oro_message_queue.status_calculator.query_calculator',
            'oro_message_queue.status_calculator.status_calculator_resolver',
            'oro_message_queue.job.dependent_job_processor',
            'oro_message_queue.job.dependent_job_service',
            'oro_message_queue.job.grid.root_job_action_configuration',
            'oro_message_queue.job.security_aware_extension',
            'oro_message_queue.job.root_job_status_extension',
            'oro_message_queue.job.out_of_memory_job_extension',
            'oro_message_queue.client.security_aware_driver_factory',
            'oro_message_queue.platform.optional_listener_driver_factory',
            'oro_message_queue.client.driver',
            'oro_message_queue.client.message_producer',
            'oro_message_queue.router.message_router',
            'oro_message_queue.client.message_processor_registry',
            'oro_message_queue.client.meta.topic_meta_registry',
            'oro_message_queue.client.meta.destination_meta_registry',
            'oro_message_queue.client.meta.topic_description_provider',
            'oro_message_queue.client.noop_message_processor',
            'oro_message_queue.client.extension.create_queue',
            'oro_message_queue.client.extension.message_processor_router',
            'oro_message_queue.client.queue_consumer',
            'oro_message_queue.client.created_queues',
            'oro_message_queue.profiler.message_queue_collector',
            'oro_message_queue.client.buffered_message_producer',
            'oro_message_queue.client.message_buffer_manager',
            'oro_message_queue.client.message_filter',
            'oro_message_queue.client.dbal_transaction_watcher',
            'oro_message_queue.client.request_watcher',
            'oro_message_queue.client.command_watcher',
            'oro_message_queue.client.processor_watcher',
            'oro_message_queue.consumption.dbal.pid_file_manager',
            'oro_message_queue.consumption.dbal.cli_process_manager',
            'oro_message_queue.consumption.dbal.redeliver_orphan_messages_extension',
            'oro_message_queue.consumption.dbal.reject_message_on_exception_extension',
            TopicsCommand::class,
            DestinationsCommand::class,
            CreateQueuesCommand::class,
            CleanupCommand::class,
            ClientConsumeMessagesCommand::class,
            ConsumerHeartbeatCommand::class,
            TransportConsumeMessagesCommand::class,
            JobController::class,
            ApiRestJobController::class,
        ];
    }

    private function getContainer(array $parameters = []): ContainerBuilder
    {
        return new ContainerBuilder(new ParameterBag($parameters));
    }
}
