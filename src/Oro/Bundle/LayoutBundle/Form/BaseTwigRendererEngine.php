<?php

namespace Oro\Bundle\LayoutBundle\Form;

use Symfony\Component\Form\FormView;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;

/**
 * Extends TwigRendererEngine to add possability to render parent blocks without "extend"
 */
class BaseTwigRendererEngine extends TwigRendererEngine implements TwigRendererEngineInterface
{
    /**
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * @var \Twig_Template
     */
    private $template;

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
    public function setEnvironment(\Twig_Environment $environment)
    {
        parent::setEnvironment($environment);
        $this->environment = $environment;
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
    public function renderBlock(FormView $view, $resource, $blockName, array $variables = [])
    {
        $cacheKey = $view->vars[self::CACHE_KEY_VAR];

        $context = $this->environment->mergeGlobals($variables);

        ob_start();

        // By contract,This method can only be called after getting the resource
        // (which is passed to the method). Getting a resource for the first time
        // (with an empty cache) is guaranteed to invoke loadResourcesFromTheme(),
        // where the property $template is initialized.

        // We do not call renderBlock here to avoid too many nested level calls
        // (XDebug limits the level to 100 by default)
        if (array_key_exists($cacheKey, $this->overrideResources)) {
            $resource = $this->overrideResources[$cacheKey];
        } else {
            $resource = $this->resources[$cacheKey];
        }

        $this->template->displayBlock($blockName, $context, $resource);

        unset(
            $this->parentResourceOffsets[$cacheKey],
            $this->parentResourceHierarchyLevels[$cacheKey],
            $this->overrideResources[$cacheKey]
        );

        return ob_get_clean();
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
    protected function loadResourcesFromTheme($cacheKey, &$theme)
    {
        parent::loadResourcesFromTheme($cacheKey, $theme);

        if (null === $this->template) {
            // Store the first \Twig_Template instance that we find so that
            // we can call displayBlock() later on. It doesn't matter *which*
            // template we use for that, since we pass the used blocks manually
            // anyway.
            $this->template = $theme;
        }

        // Use a separate variable for the inheritance traversal, because
        // theme is a reference and we don't want to change it.
        $currentTheme = $theme;

        $context = $this->environment->mergeGlobals([]);

        // The do loop takes care of template inheritance.
        // Add blocks from all templates including parent resources in the inheritance tree.
        do {
            foreach ($currentTheme->getBlocks() as $block => $blockData) {
                if (!array_key_exists($block, $this->resourcesHierarchy)) {
                    $this->resources[$cacheKey][$block] = $blockData;
                    $this->resourcesHierarchy[$block] = [$blockData];
                } else {
                    array_unshift($this->resourcesHierarchy[$block], $blockData);
                }
            }
        } while (false !== $currentTheme = $currentTheme->getParent($context));
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceHierarchyLevel(FormView $view, array $blockNameHierarchy, $hierarchyLevel)
    {
        $cacheKey = $view->vars[self::CACHE_KEY_VAR];

        // if self::switchToNextParentResource saved hierarchy level for this block
        // we use value from self::$parentResourceHierarchyLevels
        for ($i = count($blockNameHierarchy) - 1; $i >= 0; $i--) {
            $blockName = $blockNameHierarchy[$i];
            if (isset($this->parentResourceHierarchyLevels[$cacheKey][$blockName])) {
                return $this->parentResourceHierarchyLevels[$cacheKey][$blockName]--;
            }
        }

        // else use value retrieved from AbstractRendererEngine
        return parent::getResourceHierarchyLevel($view, $blockNameHierarchy, $hierarchyLevel);
    }

    /**
     * {@inheritdoc}
     */
    public function switchToNextParentResource(FormView $view, array $blockNameHierarchy)
    {
        $cacheKey = $view->vars[self::CACHE_KEY_VAR];
        $primaryBlockName = $blockNameHierarchy[count($blockNameHierarchy) - 1];

        // here we walk through all hierarchy levels to find next parent resource
        for ($i = count($blockNameHierarchy) - 1; $i >= 0; $i--) {
            $blockName = $blockNameHierarchy[$i];
            $isHighestHierarchyLevelBlock = ($i == count($blockNameHierarchy) - 1);

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

                $this->parentResourceHierarchyLevels[$cacheKey][$primaryBlockName] = $i;

                return $resource;
            }
        }

        return false;
    }
}
