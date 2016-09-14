<?php

namespace Oro\Bundle\ConfigBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
use Oro\Bundle\ConfigBundle\Config\ApiTree\VariableDefinition;
use Oro\Bundle\ConfigBundle\Config\Tree\FieldNodeDefinition;
use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;
use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

abstract class Provider implements ProviderInterface
{
    /** @var ConfigBag */
    protected $configBag;

    /** @var array */
    protected $processedTrees = array();

    /** @var array */
    protected $processedSubTrees = array();

    /**
     * @var FeatureChecker
     */
    protected $featureChecker;

    /**
     * @param ConfigBag $configBag
     */
    public function __construct(ConfigBag $configBag)
    {
        $this->configBag = $configBag;
    }

    /**
     * @param FeatureChecker $featureChecker
     */
    public function setFeatureChecker(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiTree($path = null)
    {
        $sections = empty($path) ? [] : explode('/', $path);
        array_unshift($sections, ProcessorDecorator::API_TREE_ROOT);

        $tree            = $this->configBag->getConfig();
        $rootSectionName = null;
        foreach ($sections as $section) {
            if (!isset($tree[$section])) {
                throw new ItemNotFoundException(sprintf('Config API section "%s" is not defined.', $path));
            }
            $tree            = & $tree[$section];
            $rootSectionName = $section;
        }

        return $this->buildApiTree(
            $rootSectionName === ProcessorDecorator::API_TREE_ROOT ? '' : $rootSectionName,
            $tree
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtree($subtreeRootName)
    {
        if (!isset($this->processedSubTrees[$subtreeRootName])) {
            $treeData = $this->getTree();
            $subtree  = TreeUtils::findNodeByName($treeData, $subtreeRootName);

            if ($subtree === null) {
                throw new ItemNotFoundException(sprintf('Subtree "%s" not found', $subtreeRootName));
            }

            $this->processedSubTrees[$subtreeRootName] = $subtree;
        }

        return $this->processedSubTrees[$subtreeRootName];
    }

    /**
     * @param string $treeName
     * @param int    $correctFieldsLevel
     *
     * @throws ItemNotFoundException
     * @return GroupNodeDefinition
     */
    protected function getTreeData($treeName, $correctFieldsLevel)
    {
        if (!isset($this->processedTrees[$treeName])) {
            $treeRoot = $this->configBag->getTreeRoot($treeName);
            if ($treeRoot === false) {
                throw new ItemNotFoundException(sprintf('Tree "%s" is not defined.', $treeName));
            }

            $definition = $treeRoot;
            if ($this->featureChecker) {
                $definition = $this->filterDisabledNodes($definition);
            }
            $data = $this->buildGroupNode($definition, $correctFieldsLevel);
            $tree = new GroupNodeDefinition($treeName, $definition, $data);
            $this->processedTrees[$tree->getName()] = $tree;
        }

        return $this->processedTrees[$treeName];
    }

    /**
     * @param array $definition
     * @return array
     */
    protected function filterDisabledNodes(array $definition)
    {
        foreach ($definition as $key => &$definitionRow) {
            if (is_string($definitionRow)
                && !$this->featureChecker->isResourceEnabled($definitionRow, 'configuration')
            ) {
                unset($definition[$key]);
            } elseif (is_array($definitionRow) && array_key_exists('children', $definitionRow)) {
                if ($this->featureChecker->isResourceEnabled($key, 'configuration')) {
                    $definitionRow['children'] = $this->filterDisabledNodes($definitionRow['children']);
                } else {
                    unset($definition[$key]);
                }
            }
        }

        return $definition;
    }

    /**
     * Builds group node, called recursively
     *
     * @param array $nodes
     * @param int   $correctFieldsLevel fields should be placed on the same levels that comes from view
     * @param int   $level              current level
     *
     * @throws ItemNotFoundException
     * @throws \Exception
     * @return array
     */
    protected function buildGroupNode($nodes, $correctFieldsLevel, $level = 0)
    {
        $level++;
        foreach ($nodes as $name => $node) {
            if (is_array($node) && isset($node['children'])) {
                $group = $this->configBag->getGroupsNode($name);
                if ($group === false) {
                    throw new ItemNotFoundException(sprintf('Group "%s" is not defined.', $name));
                }

                $data  = $this->buildGroupNode($node['children'], $correctFieldsLevel, $level);
                $node  = new GroupNodeDefinition($name, array_merge($group, $nodes[$name]), $data);
                $node->setLevel($level);

                $nodes[$node->getName()] = $node;
            } else {
                if ($level !== $correctFieldsLevel) {
                    throw new \Exception(
                        sprintf('Field "%s" will not be ever rendered. Please check nesting level', $node)
                    );
                }
                $nodes[$name] = $this->buildFieldNode($node);
            }
        }

        return $nodes;
    }

    /**
     * Builds field data by name
     *
     * @param string $node field node name
     *
     * @return FieldNodeDefinition
     * @throws ItemNotFoundException
     */
    protected function buildFieldNode($node)
    {
        $fieldsRoot = $this->configBag->getFieldsRoot($node);
        if ($fieldsRoot === false) {
            throw new ItemNotFoundException(sprintf('Field "%s" is not defined.', $node));
        }

        return new FieldNodeDefinition($node, $fieldsRoot);
    }

    /**
     * @param string $sectionName
     * @param array  $tree
     *
     * @return SectionDefinition
     */
    protected function buildApiTree($sectionName, array $tree)
    {
        $section = new SectionDefinition($sectionName);
        foreach ($tree as $key => $val) {
            if ($key === 'section') {
                continue;
            }
            if (!empty($val['section'])) {
                $section->addSubSection(
                    $this->buildApiTree($key, $val)
                );
            } else {
                $section->addVariable(
                    new VariableDefinition($key, $val['type'])
                );
            }
        }

        return $section;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataTransformer($key)
    {
        return $this->configBag->getDataTransformer($key);
    }
}
