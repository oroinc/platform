<?php

namespace Oro\Bundle\LayoutBundle\Form\RendererEngine;

use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Form\Extension\Templating\TemplatingRendererEngine as BaseEngine;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;
use Symfony\Component\Form\FormView;

/**
 * Extends Templating Renderer Engine to add possibility for changing default themes after container was locked
 */
class TemplatingRendererEngine extends BaseEngine implements FormRendererEngineInterface
{
    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * @var array
     */
    protected $resources;

    /**
     * @var array
     */
    protected $parentResourceHierarchyLevels;

    /**
     * @var array same like $resources but holds list of all resources including parents instead of single resource
     */
    protected $resourcesHierarchy;

    public function __construct(EngineInterface $engine, array $defaultThemes = array())
    {
        parent::__construct($engine, $defaultThemes);

        $this->engine = $engine;
    }

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
    protected function loadResourceFromTheme($cacheKey, $blockName, $theme)
    {
        parent::loadResourceFromTheme($cacheKey, $blockName, $theme);

        if ($this->engine->exists($templateName = $theme.':'.$blockName.'.html.php')) {
            $this->resources[$cacheKey][$blockName] = $templateName;

            if (!isset($this->resourcesHierarchy[$blockName])) {
                $this->resourcesHierarchy[$blockName] = array($templateName);
            } else {
                array_unshift($this->resourcesHierarchy[$blockName], $templateName);
            }

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceHierarchyLevel(FormView $view, array $blockNameHierarchy, $hierarchyLevel)
    {
        $cacheKey = $view->vars[self::CACHE_KEY_VAR];

        for ($i = count($blockNameHierarchy) - 1; $i >= 0; $i--) {
            $blockName = $blockNameHierarchy[$i];
            if (isset($this->parentResourceHierarchyLevels[$cacheKey][$blockName])) {
                return $this->parentResourceHierarchyLevels[$cacheKey][$blockName]--;
            }
        }

        return parent::getResourceHierarchyLevel($view, $blockNameHierarchy, $hierarchyLevel);
    }

    /**
     * {@inheritdoc}
     */
    public function switchToNextParentResource(FormView $view, array $blockNameHierarchy)
    {
        $cacheKey = $view->vars[self::CACHE_KEY_VAR];
        $primaryBlockName = $blockNameHierarchy[count($blockNameHierarchy) - 1];

        for ($i = count($blockNameHierarchy) - 1; $i >= 0; $i--) {
            $blockName = $blockNameHierarchy[$i];
            $isHighestHierarchyLevelBlock = ($i == count($blockNameHierarchy) - 1);

            if (isset($this->resourcesHierarchy[$blockName])) {
                // if there is only one resource on the highest hierarchy level then
                // its only current resource there, no parent resources
                if ($isHighestHierarchyLevelBlock && count($this->resourcesHierarchy[$blockName]) < 2) {
                    continue;
                }

                $blockResources = $this->resourcesHierarchy[$blockName];
                $offsetFromTheEnd = $isHighestHierarchyLevelBlock ? 2 : 1;
                $resource = $blockResources[count($blockResources) - $offsetFromTheEnd];

                $this->resources[$cacheKey][$primaryBlockName] = $resource;
                $this->parentResourceHierarchyLevels[$cacheKey][$primaryBlockName] = $i;

                return $resource;
            }
        }

        return false;
    }
}
