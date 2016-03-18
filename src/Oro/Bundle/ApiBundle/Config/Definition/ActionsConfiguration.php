<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ActionsConfiguration extends AbstractConfigurationSection implements ConfigurationSectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(
        NodeBuilder $node,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks
    ) {
        $sectionName = 'actions';
        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();

        $parentNode
            //->ignoreExtraKeys(false) @todo: uncomment after migration to Symfony 2.8+
            ->beforeNormalization()
            ->always(
                function ($value) use ($preProcessCallbacks, $sectionName) {
                    return $this->callProcessConfigCallbacks($value, $preProcessCallbacks, $sectionName);
                }
            );
        $this->callConfigureCallbacks($node, $configureCallbacks, $sectionName);

        $parentNode->useAttributeAsKey('name')
                ->prototype('array')
                ->info('Actions configuration')
                ->treatFalseLike(array(ActionsConfig::EXCLUDE => true))
                ->treatTrueLike(array(ActionsConfig::EXCLUDE => false))
                ->treatNullLike(array(ActionsConfig::EXCLUDE => false))
                ->children()
                    ->booleanNode(ActionsConfig::EXCLUDE)->cannotBeEmpty()->defaultFalse()->end()
                    ->scalarNode(ActionsConfig::DELETE_HANDLER)->cannotBeEmpty()->end()
                    ->scalarNode(ActionsConfig::ACL_RESOURCE)->end();

        $parentNode
            ->validate()
            ->always(
                function ($value) use ($postProcessCallbacks, $sectionName) {
                    // validate delete_handler values
                    $this->validateDeleteHandlerParameter($value);
                    return $this->callProcessConfigCallbacks($value, $postProcessCallbacks, $sectionName);
                }
            );
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($section)
    {
        return $section === 'entities.entity';
    }

    /**
     * Validates delete_handler parameter. It can exists only at 'delete' action.
     *
     * @param array $value
     */
    protected function validateDeleteHandlerParameter(array $value)
    {
        foreach ($value as $actionName => $actionConfig) {
            if ($actionName !== 'delete' && array_key_exists(ActionsConfig::DELETE_HANDLER, $actionConfig)) {
                throw new InvalidConfigurationException(
                    sprintf(
                        'The "%s" action does not supports delete_handler parameter.',
                        $actionName
                    )
                );
            }
        }
    }
}
