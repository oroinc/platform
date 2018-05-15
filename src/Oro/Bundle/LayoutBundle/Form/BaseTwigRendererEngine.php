<?php

namespace Oro\Bundle\LayoutBundle\Form;

use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\FormView;
use Twig\Environment;

/**
 * Extends TwigRendererEngine to add possability to render parent blocks without "extend"
 */
class BaseTwigRendererEngine extends TwigRendererEngine implements TwigRendererEngineInterface
{
    use RendererEngineTrait;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var \Twig_Template
     */
    private $template;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $defaultThemes, Environment $environment)
    {
        $this->environment = $environment;
        parent::__construct($defaultThemes, $environment);
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
}
