<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ProcessorDecorator
{
    const ROOT          = 'oro_system_configuration';
    const GROUPS_NODE   = 'groups';
    const FIELDS_ROOT   = 'fields';
    const TREE_ROOT     = 'tree';
    const API_TREE_ROOT = 'api_tree';

    /** @var Processor */
    protected $processor;

    /** @var string[] */
    protected $variables;

    /**
     * @param Processor $processor
     * @param string[]  $variableNames
     */
    public function __construct(Processor $processor, $variableNames)
    {
        $this->processor = $processor;
        $this->variables = array_combine($variableNames, $variableNames);
    }

    /**
     * @param array $data
     *
     * @return array
     *
     * @throws InvalidConfigurationException
     */
    public function process(array $data)
    {
        $result = $this->processor->process($this->getConfigurationTree()->buildTree(), $data);

        // validate variable names
        if (isset($result[self::FIELDS_ROOT])) {
            foreach ($result[self::FIELDS_ROOT] as $varName => $varData) {
                if (!isset($this->variables[$varName]) && empty($varData['ui_only'])) {
                    throw new InvalidConfigurationException(
                        sprintf(
                            'The system configuration variable "%s" is not defined.'
                            . ' Please make sure that it is either added to bundle configuration settings'
                            . ' or marked as "ui_only" in config.',
                            $varName
                        )
                    );
                }
            }
        }

        // validate API tree
        if (isset($result[self::API_TREE_ROOT])) {
            foreach ($result[self::API_TREE_ROOT] as $key => $data) {
                $this->validateApiTreeItem(self::ROOT, $key, $data);
            }
        }

        return $result;
    }

    /**
     * Merge configs by specified rules
     *
     * @param array $source
     * @param array $newData
     *
     * @return array
     */
    public function merge($source, $newData)
    {
        // prevent key isset and is_array checks
        $source = array_merge($this->getEmptyFinalArray(), $source);

        if (!empty($newData[self::ROOT])) {
            foreach ((array)$newData[self::ROOT] as $nodeName => $node) {
                switch ($nodeName) {
                    // merge recursive all nodes in tree
                    case self::TREE_ROOT:
                    case self::API_TREE_ROOT:
                        $source[self::ROOT][$nodeName] = array_merge_recursive(
                            $source[self::ROOT][$nodeName],
                            $node
                        );
                        break;
                    // replace all overrides in other nodes
                    default:
                        $source[self::ROOT][$nodeName] = array_replace_recursive(
                            $source[self::ROOT][$nodeName],
                            $node
                        );
                }
            }
        }

        return $source;
    }

    /**
     * Returns empty array representation of valid config structure
     *
     * @return array
     */
    protected function getEmptyFinalArray()
    {
        $result = array(
            self::ROOT => array_fill_keys(
                array(self::GROUPS_NODE, self::FIELDS_ROOT, self::TREE_ROOT, self::API_TREE_ROOT),
                array()
            )
        );

        return $result;
    }

    /**
     * Getter for configuration tree
     *
     * @return TreeBuilder
     */
    protected function getConfigurationTree()
    {
        $tree = new TreeBuilder();

        $tree->root(self::ROOT)
            ->children()
                ->append($this->getGroupsNode())
                ->append($this->getFieldsNode())
                ->append($this->getTreeNode())
                ->variableNode(self::API_TREE_ROOT)->end()
            ->end();

        return $tree;
    }

    /**
     * @return NodeDefinition
     */
    protected function getGroupsNode()
    {
        $builder = new TreeBuilder();

        $node = $builder->root(self::GROUPS_NODE)
            ->prototype('array')
                ->children()
                    ->scalarNode('title')->isRequired()->end()
                    ->scalarNode('icon')->end()
                    ->scalarNode('description')->end()
                    ->scalarNode('configurator')->end()
                    ->booleanNode('page_reload')
                        ->defaultValue(false)
                    ->end()
                    ->integerNode('priority')->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * @return NodeDefinition
     */
    protected function getFieldsNode()
    {
        $builder = new TreeBuilder();

        $node = $builder->root(self::FIELDS_ROOT)
            ->prototype('array')
                ->children()
                    ->scalarNode('type')->isRequired()->end()
                    ->arrayNode('options')
                        ->prototype('variable')->end()
                    ->end()
                    ->scalarNode('acl_resource')->end()
                    ->integerNode('priority')->end()
                    ->booleanNode('ui_only')->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * @return NodeDefinition
     */
    protected function getTreeNode()
    {
        $builder = new TreeBuilder();

        $node = $builder->root(self::TREE_ROOT)
            ->prototype('array')
                ->prototype('array')
                    ->children()
                        ->arrayNode('children')
                            ->prototype('array')
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                        ->integerNode('priority')->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * @param string $path
     * @param mixed  $key
     * @param mixed  $data
     *
     * @throws InvalidTypeException
     * @throws InvalidConfigurationException
     */
    protected function validateApiTreeItem($path, $key, $data)
    {
        if (!is_string($key)) {
            throw new InvalidTypeException(
                sprintf('Array key must be a string, but got "%s". Root node: %s.', gettype($key), $path)
            );
        }
        if (is_array($data)) {
            $path = $path . '.' . $key;
            foreach ($data as $subKey => $subData) {
                $this->validateApiTreeItem($path, $subKey, $subData);
            }
        } elseif (!is_string($data)) {
            throw new InvalidTypeException(
                sprintf(
                    'The value of "%s" must be an array or a string, but got "%s".',
                    $path . '.' . $key,
                    gettype($data)
                )
            );
        } elseif (!isset($this->variables[$data])) {
            throw new InvalidConfigurationException(
                sprintf(
                    'The system configuration variable "%s" is not defined.'
                    . ' Please make sure that it is added to bundle configuration settings. Node path: %s.',
                    $data,
                    $path . '.' . $key
                )
            );
        }
    }
}
