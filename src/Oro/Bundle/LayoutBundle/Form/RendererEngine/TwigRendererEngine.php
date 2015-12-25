<?php

namespace Oro\Bundle\LayoutBundle\Form\RendererEngine;

use Symfony\Bridge\Twig\Form\TwigRendererEngine as BaseEngine;

use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;

/**
 * Extends Twig Renderer Engine to add possibility for changing default themes after container was locked
 */
class TwigRendererEngine extends BaseEngine implements FormRendererEngineInterface
{
    /**
     * {@inheritdoc}
     */
    public function addDefaultThemes($themes)
    {
        $themes = is_array($themes) ? $themes : [$themes];

        $this->defaultThemes = array_merge($this->defaultThemes, $themes);
    }
}
