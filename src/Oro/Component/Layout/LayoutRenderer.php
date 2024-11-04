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

    public function __construct(FormRendererInterface $innerRenderer, FormRendererEngineInterface $formRendererEngine)
    {
        $this->innerRenderer = $innerRenderer;
        $this->formRendererEngine = $formRendererEngine;
    }

    #[\Override]
    public function renderBlock(BlockView $view)
    {
        return $this->innerRenderer->searchAndRenderBlock($view, 'widget');
    }

    #[\Override]
    public function setBlockTheme(BlockView $view, $themes)
    {
        $this->innerRenderer->setTheme($view, $themes);
    }

    #[\Override]
    public function setFormTheme($themes)
    {
        $this->formRendererEngine->addDefaultThemes($themes);
    }
}
