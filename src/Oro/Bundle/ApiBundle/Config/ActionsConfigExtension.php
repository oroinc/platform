<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\ActionsConfiguration;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class ActionsConfigExtension extends AbstractConfigExtension
{
    protected $configuredActions = [];

    public function addActionConfig($actionName, $config)
    {
        $this->configuredActions[$actionName] = $config;
    }

    public function getEntityConfigurationSections()
    {
        return ['actions' => new ActionsConfiguration()];
    }

    public function getEntityConfigurationLoaders()
    {
        return ['actions' => new ActionsConfigLoader()];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigureCallbacks()
    {
        return [
            'actions' => function (NodeBuilder $node) {
                foreach ($this->configuredActions as $action => $configuration) {
                    if (!empty($configuration)) {
                        $configNode = $node->arrayNode($action)
                            ->prototype('array')
                            ->children()
                                ->enumNode(FiltersConfig::EXCLUSION_POLICY)
                                    ->values([FiltersConfig::EXCLUSION_POLICY_ALL, FiltersConfig::EXCLUSION_POLICY_NONE])
                                ->end();
                            foreach ($configuration as $configName => $configDefinition) {
                                if ($configDefinition['type'] === 'scalar') {
                                    $nodeDef = $configNode->scalarNode($configName);
                                }
                                if ($configDefinition['default_value']) {
                                    $nodeDef->defaultValue($configDefinition['default_value']);
                                }
                                $nodeDef->end();
                            }
                            $configNode->end()
                            ->end();
                    }
                }
            }
        ];
    }
}