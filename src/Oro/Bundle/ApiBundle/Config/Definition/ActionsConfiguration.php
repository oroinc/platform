<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

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
                ->treatFalseLike(array('exclude' => true))
                ->treatTrueLike(array('exclude' => false))
                ->treatNullLike(array('exclude' => false))
                ->children()
                    ->booleanNode('exclude')->cannotBeEmpty()->defaultFalse()->end()
                    ->scalarNode('delete_handler')->cannotBeEmpty()->end()
                    ->scalarNode('acl_resource')->end();

        $parentNode
            ->validate()
            ->always(
                function ($value) use ($postProcessCallbacks, $sectionName) {
                    if (empty($value[$sectionName])) {
                        unset($value[$sectionName]);
                    }
                    // validate delete_handler values
                    foreach ($value as $actionName => $actionConfig) {
                        if ($actionName !== 'delete' && array_key_exists('delete_handler', $actionConfig)) {
                            throw new InvalidConfigurationException(
                                sprintf(
                                    'Action "%s" does not supports delete_handler parameter',
                                    $actionName
                                )
                            );
                        }
                    }
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
}
