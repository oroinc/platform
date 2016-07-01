<?php

namespace Oro\Bundle\LayoutBundle\Form\RendererEngine;

use Symfony\Bridge\Twig\Form\TwigRendererEngine as BaseEngine;
use Symfony\Component\Form\FormView;

use Oro\Bundle\LayoutBundle\Request\LayoutHelper;

use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;

/**
 * Extends Twig Renderer Engine to add possibility for changing default themes after container was locked
 */
class TwigRendererEngine extends BaseEngine implements FormRendererEngineInterface
{
    /**
     * @var LayoutHelper
     */
    protected $layoutHelper;

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
        $twigTemplate = current($resource);
        if ($this->layoutHelper->isProfilerEnabled() && $twigTemplate instanceof \Twig_Template) {
            $variables['attr']['data-layout-debug-block-id'] = $variables['id'];
            $variables['attr']['data-layout-debug-block-template'] = $twigTemplate->getTemplateName();
        }

        return parent::renderBlock($view, $resource, $blockName, $variables);
    }

    /**
     * @param LayoutHelper $layoutHelper
     */
    public function setLayoutHelper(LayoutHelper $layoutHelper)
    {
        $this->layoutHelper = $layoutHelper;
    }
}
