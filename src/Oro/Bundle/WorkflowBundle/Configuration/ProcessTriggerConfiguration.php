<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Cron\CronExpression;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class ProcessTriggerConfiguration implements ConfigurationInterface
{
    /**
     * @param array $configs
     * @return array
     */
    public function processConfiguration(array $configs)
    {
        $processor = new Processor();
        return $processor->processConfiguration($this, array($configs));
    }

    /**
     * @param ArrayNodeDefinition $nodeDefinition
     * @return ArrayNodeDefinition
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function addTriggerNodes(ArrayNodeDefinition $nodeDefinition)
    {
        $nodeDefinition
            ->children()
                ->enumNode('event')
                    ->defaultNull()
                    ->values(ProcessTrigger::getAllowedEvents())
                ->end()
                ->scalarNode('field')
                    ->defaultNull()
                ->end()
                ->integerNode('priority')
                    ->defaultValue(Job::PRIORITY_DEFAULT)
                ->end()
                ->booleanNode('queued')
                    ->defaultFalse()
                ->end()
                ->scalarNode('time_shift')
                    ->defaultNull()
                    ->validate()
                        ->always(
                            function ($value) {
                                // if value is an integer value
                                $integerValue = filter_var($value, FILTER_VALIDATE_INT);
                                if (false !== $integerValue) {
                                    return $integerValue;
                                }

                                // if value is DateInterval spec
                                try {
                                    return ProcessTrigger::convertDateIntervalToSeconds(new \DateInterval($value));
                                } catch (\Exception $e) {
                                    throw new \LogicException(
                                        sprintf('Time shift "%s" is not compatible with DateInterval', $value)
                                    );
                                }
                            }
                        )
                    ->end()
                ->end()
                ->scalarNode('cron')
                    ->defaultNull()
                    ->validate()
                        ->always(
                            function ($value) {
                                if ($value !== null) {
                                    // validate expression string
                                    CronExpression::factory($value);
                                }

                                return $value;
                            }
                        )
                    ->end()
                ->end()
            ->end()
            ->validate()
                ->always(
                    function ($data) {
                        if ($data['event'] && $data['cron']) {
                            throw new \LogicException('Only one child node "event" or "cron" must be configured.');
                        }

                        if ($data['cron'] && ($data['field'] || $data['queued'] || $data['time_shift'])) {
                            throw new \LogicException(
                                'Nodes "field", "queued" and "time_shift" are only allowed with event node.'
                            );
                        }

                        if ($data['field'] && $data['event'] !== ProcessTrigger::EVENT_UPDATE) {
                            throw new \LogicException('Field is only allowed for update event');
                        }

                        return $data;
                    }
                )
            ->end();

        return $nodeDefinition;
    }


    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('configuration');
        $this->addTriggerNodes($rootNode);

        return $treeBuilder;
    }
}
