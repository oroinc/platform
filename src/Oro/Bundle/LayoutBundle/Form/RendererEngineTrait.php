<?php

namespace Oro\Bundle\LayoutBundle\Form;

use Symfony\Component\Form\AbstractRendererEngine;
use Symfony\Component\Form\FormView;

/**
 * This trait provides common functionality for renderer engines. It can be used
 * only with classes that extend Symfony\Component\Form\AbstractRendererEngine
 */
trait RendererEngineTrait
{
    /**
     * @var array
     */
    protected $resources = [];

    /**
     * @var array holds resources that were override by switchToNextParentResource
     */
    protected $overrideResources = [];

    /**
     * @var array holds current hierarchy level for rendering a parent resource.
     *            we use it in self::getResourceHierarchyLevel to cheat Renderer.
     *            it was implemented to do not modify Symfony/Form code
     */
    protected $parentResourceHierarchyLevels = [];

    /**
     * @var array holds current offset for parent resource. when we render parent block for
     *            a first time offset is 2 for highest hierarchy level and 1 for others.
     *            when we render parent block second time offset is previous offset - 1 and so on.
     *            if offset is bigger then number of resources on current hierarchy level
     *            then we move to the lower hierarchy level and set current offset to 1
     */
    protected $parentResourceOffsets = [];

    /**
     * @var array similar to $resources but holds list of all resources for the block
     *            instead of the resource with highest priority. key is block name
     */
    protected $resourcesHierarchy = [];

    /**
     * {@inheritdoc}
     */
    public function addDefaultThemes($themes)
    {
        $themes = is_array($themes) ? $themes : [$themes];

        $this->defaultThemes = array_merge($this->defaultThemes, $themes);
    }

    /**
     * {@inheritdoc}
     */
    public function switchToNextParentResource(FormView $view, array $blockNameHierarchy, $hierarchyLevel)
    {
        $cacheKey = $view->vars[AbstractRendererEngine::CACHE_KEY_VAR];
        $primaryBlockName = $blockNameHierarchy[count($blockNameHierarchy) - 1];

        // here we walk through all hierarchy levels to find next parent resource
        for ($i = $hierarchyLevel; $i >= 0; $i--) {
            $blockName = $blockNameHierarchy[$i];
            $isHighestHierarchyLevelBlock = ($i == $hierarchyLevel);

            if (array_key_exists($blockName, $this->resourcesHierarchy)) {
                // if there is only one resource on the highest hierarchy level
                // then its only current resource there, no parent resources
                if ($isHighestHierarchyLevelBlock && count($this->resourcesHierarchy[$blockName]) < 2) {
                    continue;
                }

                if (!isset($this->parentResourceOffsets[$cacheKey][$blockName])) {
                    $offsetFromTheEnd = $isHighestHierarchyLevelBlock ? 2 : 1;
                    $this->parentResourceOffsets[$cacheKey][$blockName] = $offsetFromTheEnd;
                } else {
                    $offsetFromTheEnd = ++$this->parentResourceOffsets[$cacheKey][$blockName];
                    if ($offsetFromTheEnd > count($this->resourcesHierarchy[$blockName])) {
                        continue;
                    }
                }

                $blockResources = $this->resourcesHierarchy[$blockName];
                $resource = $blockResources[count($blockResources) - $offsetFromTheEnd];

                $this->overrideResources[$cacheKey] = $this->resources[$cacheKey];
                $this->overrideResources[$cacheKey][$primaryBlockName] = $resource;

                $this->parentResourceHierarchyLevels[$cacheKey][$primaryBlockName][$hierarchyLevel] = $i;

                return $resource;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceForBlockName(FormView $view, $blockName)
    {
        $cacheKey = $view->vars[self::CACHE_KEY_VAR];

        if (array_key_exists($cacheKey, $this->overrideResources)) {
            return $this->overrideResources[$cacheKey][$blockName];
        }

        return parent::getResourceForBlockName($view, $blockName);
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceHierarchyLevel(FormView $view, array $blockNameHierarchy, $hierarchyLevel)
    {
        $cacheKey = $view->vars[self::CACHE_KEY_VAR];

        // if self::switchToNextParentResource saved hierarchy level for this block
        // we use value from self::$parentResourceHierarchyLevels
        $primaryBlockName = $blockNameHierarchy[count($blockNameHierarchy) - 1];
        if (isset($this->parentResourceHierarchyLevels[$cacheKey][$primaryBlockName][$hierarchyLevel])) {
            return $this->parentResourceHierarchyLevels[$cacheKey][$primaryBlockName][$hierarchyLevel];
        }

        // else use value retrieved from AbstractRendererEngine
        return parent::getResourceHierarchyLevel($view, $blockNameHierarchy, $hierarchyLevel);
    }
}
