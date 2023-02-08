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
    private ActionsConfiguration $actionsConfiguration;
    private FiltersConfiguration $filtersConfiguration;
    private SortersConfiguration $sortersConfiguration;

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
        $this->sortersConfiguration = new SortersConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function setSettings(ConfigurationSettingsInterface $settings): void
    {
        parent::setSettings($settings);
        $this->actionsConfiguration->setSettings($settings);
        $this->filtersConfiguration->setSettings($settings);
        $this->sortersConfiguration->setSettings($settings);
    }

    /**
     * {@inheritdoc}
     */
    public function configure(NodeBuilder $node): void
    {
        /** @var NodeBuilder $subresourceNode */
        $subresourceNode = $node->end()
            ->useAttributeAsKey('')
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
                ->values([ConfigUtil::TO_MANY, ConfigUtil::TO_ONE, ConfigUtil::COLLECTION])
            ->end();

        $this->actionsConfiguration->configure(
            $node->arrayNode(ConfigUtil::ACTIONS)->children()
        );
        $this->filtersConfiguration->configure(
            $node->arrayNode(ConfigUtil::FILTERS)->children()
        );
        $this->sortersConfiguration->configure(
            $node->arrayNode(ConfigUtil::SORTERS)->children()
        );
    }

    protected function postProcessSubresourceConfig(array $config): array
    {
        if (!empty($config[ConfigUtil::TARGET_TYPE]) && ConfigUtil::COLLECTION === $config[ConfigUtil::TARGET_TYPE]) {
            $config[ConfigUtil::TARGET_TYPE] = ConfigUtil::TO_MANY;
        }
        if (empty($config[ConfigUtil::ACTIONS])) {
            unset($config[ConfigUtil::ACTIONS]);
        }
        if (empty($config[ConfigUtil::FILTERS])) {
            unset($config[ConfigUtil::FILTERS]);
        }
        if (empty($config[ConfigUtil::SORTERS])) {
            unset($config[ConfigUtil::SORTERS]);
        }

        return $config;
    }
}
