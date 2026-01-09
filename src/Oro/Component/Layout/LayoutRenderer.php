<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Form\FormRendererInterface;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;

/**
 * Renders layout blocks using form rendering infrastructure.
 *
 * This renderer delegates block rendering to a form renderer and manages block and form themes
 * through a form renderer engine, providing a unified rendering interface for layout blocks.
 */
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
