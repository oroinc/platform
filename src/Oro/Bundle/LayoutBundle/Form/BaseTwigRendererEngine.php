<?php

namespace Oro\Bundle\LayoutBundle\Form;

use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\FormView;

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
    protected $resources;

    /**
     * @var array
     */
    protected $parentResourceHierarchyLevels;

    /**
     * @var array same like $resources but holds list of all resources including parents instead of single resource
     */
    protected $resourcesHierarchy;

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
    public function renderBlock(FormView $view, $resource, $blockName, array $variables = array())
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
        $this->template->displayBlock($blockName, $context, $this->resources[$cacheKey]);

        return ob_get_clean();
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

        $context = $this->environment->mergeGlobals(array());

        // The do loop takes care of template inheritance.
        // Add blocks from all templates including parent resources in the inheritance tree.
        do {
            foreach ($currentTheme->getBlocks() as $block => $blockData) {
                if (!isset($this->resourcesHierarchy[$block])) {
                    $this->resources[$cacheKey][$block] = $blockData;
                    $this->resourcesHierarchy[$block] = array($blockData);
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
