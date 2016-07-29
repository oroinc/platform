<?php

namespace Oro\Bundle\LayoutBundle\Form;

use Symfony\Bridge\Twig\Form\TwigRendererEngine as BaseEngine;
use Symfony\Component\Form\FormView;

use Oro\Bundle\LayoutBundle\Request\LayoutHelper;

use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;

class TwigRendererEngine extends BaseEngine
{
    /**
     * @var LayoutHelper
     */
    protected $layoutHelper;

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
