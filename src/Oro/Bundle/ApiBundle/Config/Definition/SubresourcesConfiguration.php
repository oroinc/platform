<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The configuration of elements in "subresources" section.
 */
class SubresourcesConfiguration extends AbstractConfigurationSection
{
    /** @var ActionsConfiguration */
    private $actionsConfiguration;

    /** @var FiltersConfiguration */
    private $filtersConfiguration;

    /**
     * @param string[]               $permissibleActions
     * @param FilterOperatorRegistry $filterOperatorRegistry
     */
    public function __construct(array $permissibleActions, FilterOperatorRegistry $filterOperatorRegistry)
    {
        $this->actionsConfiguration = new ActionsConfiguration(
            $permissibleActions,
            'subresources.subresource.action'
        );
        $this->filtersConfiguration = new FiltersConfiguration($filterOperatorRegistry);
    }

    /**
     * {@inheritdoc}
     */
    public function setSettings(ConfigurationSettingsInterface $settings): void
    {
        parent::setSettings($settings);
        $this->actionsConfiguration->setSettings($settings);
        $this->filtersConfiguration->setSettings($settings);
    }

    /**
     * {@inheritdoc}
     */
    public function configure(NodeBuilder $node): void
    {
        /** @var NodeBuilder $subresourceNode */
        $subresourceNode = $node->end()
            ->useAttributeAsKey('name')
            ->normalizeKeys(false)
            ->prototype('array')
                ->treatFalseLike([ConfigUtil::EXCLUDE => true])
                ->treatTrueLike([ConfigUtil::EXCLUDE => false])
                ->treatNullLike([ConfigUtil::EXCLUDE => false])
                ->children();
        $subresourceNode
            ->booleanNode(ConfigUtil::EXCLUDE)->end();
        $this->configureSubresourceNode($subresourceNode);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(string $section): bool
    {
        return 'entities.entity' === $section;
    }

    /**
     * @param NodeBuilder $node
     */
    protected function configureSubresourceNode(NodeBuilder $node): void
    {
        $sectionName = 'subresources.subresource';

        /** @var ArrayNodeDefinition $parentNode */
        $parentNode = $node->end();
        $this->callConfigureCallbacks($node, $sectionName);
        $this->addPreProcessCallbacks($parentNode, $sectionName);
        $this->addPostProcessCallbacks(
            $parentNode,
            $sectionName,
            function ($value) {
                return $this->postProcessSubresourceConfig($value);
            }
        );

        $node
            ->scalarNode(ConfigUtil::TARGET_CLASS)->end()
            ->enumNode(ConfigUtil::TARGET_TYPE)
                ->values(['to-many', 'to-one', 'collection'])
            ->end();

        $this->actionsConfiguration->configure(
            $node->arrayNode(ConfigUtil::ACTIONS)->children()
        );
        $this->filtersConfiguration->configure(
            $node->arrayNode(ConfigUtil::FILTERS)->children()
        );
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function postProcessSubresourceConfig(array $config): array
    {
        if (!empty($config[ConfigUtil::TARGET_TYPE])) {
            if ('collection' === $config[ConfigUtil::TARGET_TYPE]) {
                $config[ConfigUtil::TARGET_TYPE] = 'to-many';
            }
        } elseif (!empty($config[ConfigUtil::TARGET_CLASS])) {
            $config[ConfigUtil::TARGET_TYPE] = 'to-one';
        }
        if (empty($config[ConfigUtil::ACTIONS])) {
            unset($config[ConfigUtil::ACTIONS]);
        }
        if (empty($config[ConfigUtil::FILTERS])) {
            unset($config[ConfigUtil::FILTERS]);
        }

        return $config;
    }
}
