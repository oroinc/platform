<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines a schema of "Resources/config/oro/api.yml" files.
 */
class ApiConfiguration implements ConfigurationInterface
{
    public const EXCLUSIONS_SECTION = 'exclusions';
    public const INCLUSIONS_SECTION = 'inclusions';
    public const ENTITY_ATTRIBUTE = 'entity';
    public const FIELD_ATTRIBUTE = 'field';
    public const ENTITY_ALIASES_SECTION = 'entity_aliases';
    public const ENTITIES_SECTION = 'entities';

    public const ROOT_NODE = 'api';

    private ConfigurationSettingsInterface $settings;
    private int $maxNestingLevel;

    public function __construct(ConfigExtensionRegistry $extensionRegistry, ?int $maxNestingLevel = null)
    {
        $this->settings = $extensionRegistry->getConfigurationSettings();
        $this->maxNestingLevel = $maxNestingLevel ?? $extensionRegistry->getMaxNestingLevel();
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();
        $children = $rootNode->children();

        $this->addEntityAliasesSection($children);

        $entityNode = $this->addEntitySection(
            $children,
            $this->createEntityConfiguration(self::ENTITIES_SECTION, new EntityDefinitionConfiguration())
        );
        $entityNode->booleanNode(ConfigUtil::EXCLUDE);

        $rootNode
            ->validate()
            ->always(
                function ($value) {
                    return $this->postProcessConfig($value);
                }
            );

        return $treeBuilder;
    }

    private function createEntityConfiguration(
        string $sectionName,
        TargetEntityDefinitionConfiguration $definitionSection
    ): EntityConfiguration {
        return new EntityConfiguration(
            $sectionName,
            $definitionSection,
            $this->settings,
            $this->maxNestingLevel
        );
    }

    private function addEntityAliasesSection(NodeBuilder $parentNode): void
    {
        $node = $parentNode
            ->arrayNode(self::ENTITY_ALIASES_SECTION)
                ->useAttributeAsKey('')
                ->prototype('array')
                ->children();
        $configuration = new EntityAliasesConfiguration();
        $configuration->configure($node);
    }

    private function addEntitySection(NodeBuilder $parentNode, EntityConfiguration $configuration): NodeBuilder
    {
        $node = $parentNode
            ->arrayNode($configuration->getSectionName())
                ->useAttributeAsKey('')
                ->prototype('array')
                ->children();
        $configuration->configure($node);

        return $node;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function postProcessConfig(array $config): array
    {
        $config[self::EXCLUSIONS_SECTION] = [];
        $config[self::INCLUSIONS_SECTION] = [];
        if (!empty($config[self::ENTITIES_SECTION])) {
            foreach ($config[self::ENTITIES_SECTION] as $entityClass => &$entityConfig) {
                if (!empty($entityConfig)) {
                    if (\array_key_exists(ConfigUtil::EXCLUDE, $entityConfig)) {
                        if ($entityConfig[ConfigUtil::EXCLUDE]) {
                            $config[self::EXCLUSIONS_SECTION][] = [self::ENTITY_ATTRIBUTE => $entityClass];
                        } else {
                            $config[self::INCLUSIONS_SECTION][] = [self::ENTITY_ATTRIBUTE => $entityClass];
                        }
                        unset($entityConfig[ConfigUtil::EXCLUDE]);
                    }
                    if (!empty($entityConfig[ConfigUtil::FIELDS])) {
                        foreach ($entityConfig[ConfigUtil::FIELDS] as $fieldName => $fieldConfig) {
                            if (\array_key_exists(ConfigUtil::EXCLUDE, $fieldConfig)
                                && !$fieldConfig[ConfigUtil::EXCLUDE]
                            ) {
                                $config[self::INCLUSIONS_SECTION][] = [
                                    self::ENTITY_ATTRIBUTE => $entityClass,
                                    self::FIELD_ATTRIBUTE  => $fieldName
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $config;
    }
}
