<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ApiConfiguration implements ConfigurationInterface
{
    const EXCLUSIONS_SECTION = 'exclusions';
    const INCLUSIONS_SECTION = 'inclusions';
    const ENTITY_ATTRIBUTE   = 'entity';
    const FIELD_ATTRIBUTE    = 'field';
    const ENTITIES_SECTION   = 'entities';
    const RELATIONS_SECTION  = 'relations';

    /** @var ConfigurationSettingsInterface */
    protected $settings;

    /** @var int */
    protected $maxNestingLevel;

    /**
     * @param ConfigExtensionRegistry $extensionRegistry
     * @param int|null                $maxNestingLevel
     */
    public function __construct(ConfigExtensionRegistry $extensionRegistry, $maxNestingLevel = null)
    {
        $this->settings = $extensionRegistry->getConfigurationSettings();
        $this->maxNestingLevel = null !== $maxNestingLevel
            ? $maxNestingLevel
            : $extensionRegistry->getMaxNestingLevel();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_api');
        $children    = $rootNode->children();

        $entityNode = $this->addEntitySection(
            $children,
            $this->createEntityConfiguration(self::ENTITIES_SECTION, new EntityDefinitionConfiguration())
        );
        $entityNode->booleanNode(ConfigUtil::EXCLUDE);

        $this->addEntitySection(
            $children,
            $this->createEntityConfiguration(self::RELATIONS_SECTION, new RelationDefinitionConfiguration())
        );

        $rootNode
            ->validate()
            ->always(
                function ($value) {
                    return $this->postProcessConfig($value);
                }
            );

        return $treeBuilder;
    }

    /**
     * @param string                              $sectionName
     * @param TargetEntityDefinitionConfiguration $definitionSection
     *
     * @return EntityConfiguration
     */
    protected function createEntityConfiguration(
        $sectionName,
        TargetEntityDefinitionConfiguration $definitionSection
    ) {
        return new EntityConfiguration(
            $sectionName,
            $definitionSection,
            $this->settings,
            $this->maxNestingLevel
        );
    }

    /**
     * @param NodeBuilder         $parentNode
     * @param EntityConfiguration $configuration
     *
     * @return NodeBuilder
     */
    protected function addEntitySection(NodeBuilder $parentNode, EntityConfiguration $configuration)
    {
        $node = $parentNode
            ->arrayNode($configuration->getSectionName())
                ->useAttributeAsKey('name')
                ->prototype('array')
                ->children();
        $configuration->configure($node);

        return $node;
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function postProcessConfig(array $config)
    {
        $config[self::EXCLUSIONS_SECTION] = [];
        $config[self::INCLUSIONS_SECTION] = [];
        if (!empty($config[self::ENTITIES_SECTION])) {
            foreach ($config[self::ENTITIES_SECTION] as $entityClass => &$entityConfig) {
                if (!empty($entityConfig)) {
                    if (array_key_exists(ConfigUtil::EXCLUDE, $entityConfig)) {
                        if ($entityConfig[ConfigUtil::EXCLUDE]) {
                            $config[self::EXCLUSIONS_SECTION][] = [self::ENTITY_ATTRIBUTE => $entityClass];
                        } else {
                            $config[self::INCLUSIONS_SECTION][] = [self::ENTITY_ATTRIBUTE => $entityClass];
                        }
                        unset($entityConfig[ConfigUtil::EXCLUDE]);
                    }
                    if (!empty($entityConfig[ConfigUtil::FIELDS])) {
                        foreach ($entityConfig[ConfigUtil::FIELDS] as $fieldName => $fieldConfig) {
                            if (array_key_exists(ConfigUtil::EXCLUDE, $fieldConfig)
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
