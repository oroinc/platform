<?php

namespace Oro\Component\Layout\Form\RendererEngine;

use Symfony\Component\Form\FormRendererEngineInterface as BaseFormRendererEngineInterfaceInterface;

/**
 * {@inheritdoc}
 */
interface FormRendererEngineInterface extends BaseFormRendererEngineInterfaceInterface
{
    /**
     * Sets the theme(s) to be used for rendering a view and its children.
     *
     * @param string|string[] $themes The theme(s). The type of these themes
     * is open to the implementation.
     */
    public function addDefaultThemes($themes);
}
