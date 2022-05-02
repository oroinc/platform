<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory\TransportFactoryInterface;
use Oro\Component\MessageQueue\Client\NoopMessageProcessor;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /** @var TransportFactoryInterface[] */
    private array $factories;
    private string $environment;

    public function __construct(array $factories, string $environment)
    {
        $this->factories = $factories;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('oro_message_queue');
        $rootNode = $builder->getRootNode();

        $transportNode = $rootNode->children()
            ->arrayNode('transport')
            ->addDefaultsIfNotSet()
            ->info('List of available transports with their configurations.');
        foreach ($this->factories as $factory) {
            $factory->addConfiguration($transportNode);
        }
        $transportNode->end();

        $this->appendClientConfiguration($rootNode);

        $rootNode->children()
            ->arrayNode('persistent_services')
                ->info('A list of services that must not be removed from the container once the message is processed.')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('persistent_processors')
                ->info(
                    'A list of processors that must not be removed from the container once the message is processed.'
                )
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('security_agnostic_topics')
                ->info('A list of topics that should always be processed without a security context.')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('security_agnostic_processors')
                    ->info('A list of processors that should always be processed without a security context.')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('consumer')
                ->children()
                    ->integerNode('heartbeat_update_period')
                        ->min(0)
                        ->defaultValue(15)
                        ->info(
                            'Consumer heartbeat update period in minutes. To disable the checks, set this option to 0'
                        )
                    ->end()
                ->end()
            ->end()
            ->arrayNode('time_before_stale')
                ->info(
                    "The maximum time for a unique job execution.\n"
                    . "If a job is still running longer than that,\n"
                    . "it is possible to create a new copy of a unique job (with the same name).\n"
                    . 'The old job is marked as "stale" in this case.'
                )
                ->example([
                    '# default' => 'X',
                    '# jobs' => ['# some_job_type_name' => 'Y']
                ])
                ->children()
                    ->integerNode('default')
                        ->min(-1)
                        ->info(
                            "The number of seconds of inactivity to qualify a job as stale.\n"
                            . "If this attribute is not set or set to -1, jobs will never be qualified as stale.\n"
                            . "It means that if a unique job is not properly removed after it is finished,\n"
                            . 'it will be blocking other jobs of that type until it is manually interrupted.'
                        )
                    ->end()
                    ->arrayNode('jobs')
                        ->useAttributeAsKey('job_name')
                        ->info(
                            "The number of seconds of inactivity to qualify jobs of this type as stale.\n"
                            . "To disable staling jobs for the given job type, set this option to -1.\n"
                            . 'The key can be a whole job name or a part of it from the beginning of string to any "."'
                        )
                        ->example([
                            '# bundle_name.processor_name.entity_name.user' => 'X',
                            '# bundle_ name.processor_name.entity_name' => 'Y',
                            '# bundle_name.processor_name' => 'Z',
                        ])
                        ->prototype('integer')->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }

    private function appendClientConfiguration(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
            ->arrayNode('client')
                ->addDefaultsIfNotSet()
                ->info('Consumption client configuration.')
                ->children()
                    ->booleanNode('traceable_producer')
                        ->defaultFalse()
                    ->end()
                    ->scalarNode('prefix')
                        ->defaultValue('oro')
                    ->end()
                    ->scalarNode('default_destination')
                        ->defaultValue('default')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('default_topic')
                        ->defaultValue('default')
                        ->cannotBeEmpty()
                    ->end()
                    ->arrayNode('redelivery')
                        ->addDefaultsIfNotSet()
                        ->info('Redelivery message extension configuration.')
                        ->children()
                            ->booleanNode('enabled')
                                ->defaultValue(true)
                                ->info(
                                    "If redelivery enabled than new copied message will be published\n"
                                    . "to message broker and old one will be REJECTED when error\n"
                                    . 'was occurred during message processing.'
                                )
                            ->end()
                            ->integerNode('delay_time')
                                ->min(1)
                                ->defaultValue(10)
                                ->info(
                                    "Time through which message will be re-published to the broker,\n"
                                    . 'old one will be REJECTED immediately.'
                                )
                            ->end()
                        ->end()
                    ->end()
                    ->enumNode('noop_status')
                        ->info('Status that must be set for messages not claimed by any message processor.')
                        ->defaultValue(
                            $this->environment === 'prod'
                                ? NoopMessageProcessor::REQUEUE
                                : NoopMessageProcessor::THROW_EXCEPTION
                        )
                        ->values(
                            [
                                NoopMessageProcessor::ACK,
                                NoopMessageProcessor::REJECT,
                                NoopMessageProcessor::REQUEUE,
                                NoopMessageProcessor::THROW_EXCEPTION
                            ]
                        )
                    ->end()
                ->end()
            ->end();
    }
}
