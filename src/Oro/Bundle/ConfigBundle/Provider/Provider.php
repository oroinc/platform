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

use Oro\Bundle\SecurityBundle\SecurityFacade;

abstract class Provider implements ProviderInterface
{
    /** @var ConfigBag */
    protected $configBag;

    /** @var array */
    protected $processedTrees = array();

    /** @var array */
    protected $processedSubTrees = array();

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ConfigBag      $configBag
     * @param SecurityFacade $securityFacade
     */
    public function __construct(ConfigBag $configBag, SecurityFacade $securityFacade)
    {
        $this->configBag      = $configBag;
        $this->securityFacade = $securityFacade;
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

            $definition                             = $treeRoot;
            $data                                   = $this->buildGroupNode($definition, $correctFieldsLevel);
            $tree                                   = new GroupNodeDefinition($treeName, $definition, $data);
            $this->processedTrees[$tree->getName()] = $tree;
        }

        return $this->processedTrees[$treeName];
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
     * Check ACL resource
     *
     * @param string $resourceName
     *
     * @return bool
     */
    protected function checkIsGranted($resourceName)
    {
        return $this->securityFacade->isGranted($resourceName);
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
