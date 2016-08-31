<?php

namespace Oro\Component\Layout;

use Symfony\Component\Form\FormView;

use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;
use Oro\Component\Layout\Form\FormRendererInterface;

/**
 * Heavily inspired by FormRenderer class
 *
 * @see \Symfony\Component\Form\FormRenderer
 */
class Renderer implements FormRendererInterface
{
    const CACHE_KEY_VAR = 'unique_block_prefix';

    /**
     * @var FormRendererEngineInterface
     */
    protected $engine;

    /**
     * @var array
     */
    private $blockNameHierarchyMap = [];

    /**
     * @var array
     */
    private $hierarchyLevelMap = [];

    /**
     * @var array
     */
    private $variableStack = [];

    /**
     * @param FormRendererEngineInterface $engine
     */
    public function __construct(FormRendererEngineInterface $engine)
    {
        $this->engine = $engine;
    }

    /**
     * {@inheritdoc}
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * {@inheritdoc}
     */
    public function setTheme(FormView $view, $themes)
    {
        $this->engine->setTheme($view, $themes);
    }

    /**
     * {@inheritdoc}
     */
    public function renderCsrfToken($tokenId)
    {
        throw new \LogicException('Method must not be called during layout rendering.');
    }

    /**
     * {@inheritdoc}
     */
    public function renderBlock(FormView $view, $blockName, array $variables = [])
    {
        $resource = $this->engine->getResourceForBlockName($view, $blockName);

        if (!$resource) {
            throw new \LogicException(sprintf('No block "%s" found while rendering the form.', $blockName));
        }

        $viewCacheKey = $view->vars[self::CACHE_KEY_VAR];

        // The variables are cached globally for a view (instead of for the
        // current suffix)
        if (!isset($this->variableStack[$viewCacheKey])) {
            $this->variableStack[$viewCacheKey] = [];

            // The default variable scope contains all view variables, merged with
            // the variables passed explicitly to the helper
            $scopeVariables = $view->vars;

            $varInit = true;
        } else {
            // Reuse the current scope and merge it with the explicitly passed variables
            $scopeVariables = end($this->variableStack[$viewCacheKey]);

            $varInit = false;
        }

        // Merge the passed with the existing attributes
        if (isset($variables['attr'], $scopeVariables['attr'])) {
            $variables['attr'] = array_replace($scopeVariables['attr'], $variables['attr']);
        }

        // Merge the passed with the exist *label* attributes
        if (isset($variables['label_attr'], $scopeVariables['label_attr'])) {
            $variables['label_attr'] = array_replace($scopeVariables['label_attr'], $variables['label_attr']);
        }

        // Do not use array_replace_recursive(), otherwise array variables
        // cannot be overwritten
        $variables = array_replace($scopeVariables, $variables);

        $this->variableStack[$viewCacheKey][] = $variables;

        // Do the rendering
        $html = $this->engine->renderBlock($view, $resource, $blockName, $variables);

        // Clear the stack
        array_pop($this->variableStack[$viewCacheKey]);

        if ($varInit) {
            unset($this->variableStack[$viewCacheKey]);
        }

        return $html;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * {@inheritdoc}
     */
    public function searchAndRenderBlock(
        FormView $view,
        $blockNameSuffix,
        array $variables = [],
        $renderParentBlock = false
    ) {
        $viewCacheKey = $view->vars[self::CACHE_KEY_VAR];
        $viewAndSuffixCacheKey = $viewCacheKey . $blockNameSuffix;

        if (!isset($this->blockNameHierarchyMap[$viewAndSuffixCacheKey])) {
            // INITIAL CALL
            // Calculate the hierarchy of template blocks and start on
            // the bottom level of the hierarchy (= "_<id>_<section>" block)
            $blockNameHierarchy = [];
            foreach ($view->vars['block_prefixes'] as $blockNamePrefix) {
                $blockNameHierarchy[] = $blockNamePrefix . '_' . $blockNameSuffix;
            }
            $hierarchyLevel = count($blockNameHierarchy) - 1;

            $hierarchyInit = true;
        } else {
            // RECURSIVE CALL
            // If a block recursively calls searchAndRenderBlock() again, resume rendering
            // using the parent type in the hierarchy.
            $blockNameHierarchy = $this->blockNameHierarchyMap[$viewAndSuffixCacheKey];
            $hierarchyLevel = $this->hierarchyLevelMap[$viewAndSuffixCacheKey] - 1;

            $hierarchyInit = false;
        }

        // The variables are cached globally for a view (instead of for the
        // current suffix)
        if (!isset($this->variableStack[$viewCacheKey])) {
            $this->variableStack[$viewCacheKey] = [];

            // The default variable scope contains all view variables, merged with
            // the variables passed explicitly to the helper
            $scopeVariables = $view->vars;

            $varInit = true;
        } else {
            // Reuse the current scope and merge it with the explicitly passed variables
            $scopeVariables = end($this->variableStack[$viewCacheKey]);

            $varInit = false;
        }

        // Load the resource where this block can be found
        $resource = $this->engine->getResourceForBlockNameHierarchy($view, $blockNameHierarchy, $hierarchyLevel);
        if ($renderParentBlock) {
            $resource = $this->engine->switchToNextParentResource($view, $blockNameHierarchy);
        }

        // Update the current hierarchy level to the one at which the resource was found
        $hierarchyLevel = $this->engine->getResourceHierarchyLevel($view, $blockNameHierarchy, $hierarchyLevel);

        // The actually existing block name in $resource
        $blockName = $blockNameHierarchy[$hierarchyLevel];

        // Escape if no resource exists for this block
        if (!$resource) {
            throw new \LogicException(sprintf(
                'Unable to render the form as none of the following blocks exist: "%s".',
                implode('", "', array_reverse($blockNameHierarchy))
            ));
        }

        // Merge the passed with the existing attributes
        if (isset($variables['attr'], $scopeVariables['attr'])) {
            $variables['attr'] = array_replace($scopeVariables['attr'], $variables['attr']);
        }

        // Merge the passed with the exist *label* attributes
        if (isset($variables['label_attr'], $scopeVariables['label_attr'])) {
            $variables['label_attr'] = array_replace($scopeVariables['label_attr'], $variables['label_attr']);
        }

        // Do not use array_replace_recursive(), otherwise array variables
        // cannot be overwritten
        $variables = array_replace($scopeVariables, $variables);

        $this->blockNameHierarchyMap[$viewAndSuffixCacheKey] = $blockNameHierarchy;
        $this->hierarchyLevelMap[$viewAndSuffixCacheKey] = $hierarchyLevel;

        // We also need to store the variables for the view so that we can render other
        // blocks for the same view using the same variables as in the outer block.
        $this->variableStack[$viewCacheKey][] = $variables;

        $html = $this->engine->renderBlock($view, $resource, $blockName, $variables);

        // Clear the stack
        array_pop($this->variableStack[$viewCacheKey]);

        if ($hierarchyInit) {
            unset(
                $this->blockNameHierarchyMap[$viewAndSuffixCacheKey],
                $this->hierarchyLevelMap[$viewAndSuffixCacheKey]
            );
        }

        if ($varInit) {
            unset($this->variableStack[$viewCacheKey]);
        }

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function humanize($text)
    {
        return ucfirst(trim(strtolower(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $text))));
    }
}
