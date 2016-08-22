<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Form\FormRendererInterface;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;

class LayoutRenderer implements LayoutRendererInterface
{
    /** @var FormRendererInterface */
    protected $innerRenderer;

    /** @var FormRendererEngineInterface */
    private $formRendererEngine;

    /**
     * @param FormRendererInterface $innerRenderer
     * @param FormRendererEngineInterface $formRendererEngine
     */
    public function __construct(FormRendererInterface $innerRenderer, FormRendererEngineInterface $formRendererEngine)
    {
        $this->innerRenderer = $innerRenderer;
        $this->formRendererEngine = $formRendererEngine;
    }

    /**
     * {@inheritdoc}
     */
    public function renderBlock(BlockView $view)
    {
        return $this->innerRenderer->searchAndRenderBlock($view, 'widget');
    }

    /**
     * {@inheritdoc}
     */
    public function setBlockTheme(BlockView $view, $themes)
    {
        $this->innerRenderer->setTheme($view, $themes);
    }

    /**
     * {@inheritdoc}
     */
    public function setFormTheme($themes)
    {
        $this->formRendererEngine->addDefaultThemes($themes);
    }
}
