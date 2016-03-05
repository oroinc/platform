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
    const ENTITY_ATTRIBUTE   = 'entity';
    const FIELD_ATTRIBUTE    = 'field';
    const ENTITIES_SECTION   = 'entities';
    const RELATIONS_SECTION  = 'relations';

    /** @var ConfigExtensionRegistry */
    protected $extensionRegistry = [];

    /** @var int */
    protected $maxNestingLevel;

    /**
     * @param ConfigExtensionRegistry $extensionRegistry
     * @param int|null                $maxNestingLevel
     */
    public function __construct(ConfigExtensionRegistry $extensionRegistry, $maxNestingLevel = null)
    {
        $this->extensionRegistry = $extensionRegistry;
        $this->maxNestingLevel   = null !== $maxNestingLevel
            ? $maxNestingLevel
            : $this->extensionRegistry->getMaxNestingLevel();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_api');
        $children    = $rootNode->children();

        list(
            $extraSections,
            $configureCallbacks,
            $preProcessCallbacks,
            $postProcessCallbacks
            ) = $this->extensionRegistry->getConfigurationSettings();

        $this->addExclusionsSection($children);

        $entityNode = $this->addEntitySection(
            $children,
            new EntityConfiguration(
                self::ENTITIES_SECTION,
                new EntityDefinitionConfiguration(),
                $extraSections,
                $this->maxNestingLevel
            ),
            $configureCallbacks,
            $preProcessCallbacks,
            $postProcessCallbacks
        );
        $entityNode->booleanNode(ConfigUtil::EXCLUDE);

        $this->addEntitySection(
            $children,
            new EntityConfiguration(
                self::RELATIONS_SECTION,
                new RelationDefinitionConfiguration(),
                $extraSections,
                $this->maxNestingLevel
            ),
            $configureCallbacks,
            $preProcessCallbacks,
            $postProcessCallbacks
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
     * @param NodeBuilder $parentNode
     */
    protected function addExclusionsSection(NodeBuilder $parentNode)
    {
        $parentNode
            ->arrayNode(self::EXCLUSIONS_SECTION)
            ->prototype('array')
            ->children()
            ->scalarNode(self::ENTITY_ATTRIBUTE)
            ->isRequired()
            ->cannotBeEmpty()
            ->end()
            ->scalarNode(self::FIELD_ATTRIBUTE)->end();
    }

    /**
     * @param NodeBuilder         $parentNode
     * @param EntityConfiguration $entityConfiguration
     * @param array               $configureCallbacks
     * @param array               $preProcessCallbacks
     * @param array               $postProcessCallbacks
     *
     * @return NodeBuilder
     */
    protected function addEntitySection(
        NodeBuilder $parentNode,
        EntityConfiguration $entityConfiguration,
        array $configureCallbacks,
        array $preProcessCallbacks,
        array $postProcessCallbacks
    ) {
        $node = $parentNode
            ->arrayNode($entityConfiguration->getSectionName())
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children();
        $entityConfiguration->configure($node, $configureCallbacks, $preProcessCallbacks, $postProcessCallbacks);

        return $node;
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function postProcessConfig(array $config)
    {
        if (!empty($config[self::ENTITIES_SECTION])) {
            foreach ($config[self::ENTITIES_SECTION] as $entityClass => &$entityConfig) {
                if (!empty($entityConfig) && array_key_exists(ConfigUtil::EXCLUDE, $entityConfig)) {
                    if ($entityConfig[ConfigUtil::EXCLUDE]
                        && !$this->hasEntityExclusion($config[self::EXCLUSIONS_SECTION], $entityClass)
                    ) {
                        $config[self::EXCLUSIONS_SECTION][] = [self::ENTITY_ATTRIBUTE => $entityClass];
                    }
                    unset($entityConfig[ConfigUtil::EXCLUDE]);
                }
            }
        }

        return $config;
    }

    /**
     * @param array  $exclusions
     * @param string $entityClass
     *
     * @return bool
     */
    protected function hasEntityExclusion($exclusions, $entityClass)
    {
        $result = false;
        foreach ($exclusions as $exclusion) {
            if (array_key_exists(self::ENTITY_ATTRIBUTE, $exclusion)
                && $exclusion[self::ENTITY_ATTRIBUTE] === $entityClass
                && count($exclusion) === 1
            ) {
                $result = true;
                break;
            }
        }

        return $result;
    }
}
