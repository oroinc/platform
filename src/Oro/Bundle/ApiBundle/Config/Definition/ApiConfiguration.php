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
        $config[self::EXCLUSIONS_SECTION] = [];
        $config[self::INCLUSIONS_SECTION] = [];
        if (!empty($config[self::ENTITIES_SECTION])) {
            foreach ($config[self::ENTITIES_SECTION] as $entityClass => &$entityConfig) {
                if (!empty($entityConfig)) {
                    $this->processExclusionsAndInclusions($config, $entityClass, $entityConfig);
                }
            }
        }

        return $config;
    }

    /**
     * @param array  $config
     * @param string $entityClass
     * @param array  $entityConfig
     */
    protected function processExclusionsAndInclusions(array &$config, $entityClass, array &$entityConfig)
    {
        if (array_key_exists(ConfigUtil::EXCLUDE, $entityConfig)) {
            if ($entityConfig[ConfigUtil::EXCLUDE]) {
                if (!$this->hasEntityExclusion($config[self::EXCLUSIONS_SECTION], $entityClass)) {
                    $config[self::EXCLUSIONS_SECTION][] = [self::ENTITY_ATTRIBUTE => $entityClass];
                }
            } elseif (!$this->hasEntityInclusion($config[self::INCLUSIONS_SECTION], $entityClass)) {
                $config[self::INCLUSIONS_SECTION][] = [self::ENTITY_ATTRIBUTE => $entityClass];
            }
            unset($entityConfig[ConfigUtil::EXCLUDE]);
        }
        if (!empty($entityConfig[ConfigUtil::FIELDS])) {
            foreach ($entityConfig[ConfigUtil::FIELDS] as $fieldName => $fieldConfig) {
                if (array_key_exists(ConfigUtil::EXCLUDE, $fieldConfig)
                    && !$fieldConfig[ConfigUtil::EXCLUDE]
                    && !$this->hasFieldInclusion($config[self::INCLUSIONS_SECTION], $entityClass, $fieldName)
                ) {
                    $config[self::INCLUSIONS_SECTION][] = [
                        self::ENTITY_ATTRIBUTE => $entityClass,
                        self::FIELD_ATTRIBUTE  => $fieldName
                    ];
                }
            }
        }
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

    /**
     * @param array  $inclusions
     * @param string $entityClass
     *
     * @return bool
     */
    protected function hasEntityInclusion($inclusions, $entityClass)
    {
        $result = false;
        foreach ($inclusions as $inclusion) {
            if (array_key_exists(self::ENTITY_ATTRIBUTE, $inclusion)
                && $inclusion[self::ENTITY_ATTRIBUTE] === $entityClass
                && count($inclusion) === 1
            ) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * @param array  $inclusions
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return bool
     */
    protected function hasFieldInclusion($inclusions, $entityClass, $fieldName)
    {
        $result = false;
        foreach ($inclusions as $inclusion) {
            if (array_key_exists(self::ENTITY_ATTRIBUTE, $inclusion)
                && array_key_exists(self::FIELD_ATTRIBUTE, $inclusion)
                && $inclusion[self::ENTITY_ATTRIBUTE] === $entityClass
                && $inclusion[self::FIELD_ATTRIBUTE] === $fieldName
                && count($inclusion) === 2
            ) {
                $result = true;
                break;
            }
        }

        return $result;
    }
}
