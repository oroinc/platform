<?php

namespace Oro\Bundle\ConfigBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
use Oro\Bundle\ConfigBundle\Config\DataTransformerInterface;
use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;
use Symfony\Component\Form\FormInterface;

interface ProviderInterface
{
    /**
     * Returns a tree is used to build API data
     *
     * @param string|null $path The path to API section. For example: look-and-feel/grid
     *
     * @return SectionDefinition|null
     */
    public function getApiTree($path = null);

    /**
     * Returns specified tree
     *
     * @return GroupNodeDefinition
     */
    public function getTree();

    /**
     * Return specified tree for js-tree component
     *
     * @return []
     */
    public function getJsTree();

    /**
     * Retrieve slice of specified tree in point of subtree
     *
     * @param string $subTreeName
     *
     * @return GroupNodeDefinition
     */
    public function getSubTree($subTreeName);

    /**
     * Builds form for specified tree group
     *
     * @param string $groupName
     *
     * @return FormInterface
     */
    public function getForm($groupName);

    /**
     * Lookup for first available groups if they are not specified yet
     *
     * @param string $activeGroup
     * @param string $activeSubGroup
     *
     * @return array
     */
    public function chooseActiveGroups($activeGroup, $activeSubGroup);

    /**
     * Returns a data transformer for the specified field
     *
     * @param string $key
     *
     * @return DataTransformerInterface|null
     */
    public function getDataTransformer($key);
}
