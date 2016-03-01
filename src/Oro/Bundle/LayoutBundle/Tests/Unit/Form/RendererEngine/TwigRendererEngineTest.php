<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form\RendererEngine;

use Oro\Bundle\LayoutBundle\Form\RendererEngine\TwigRendererEngine;

class TwigRendererEngineTest extends RendererEngineTest
{
    /**
     * {@inheritdoc}
     */
    public function createRendererEngine()
    {
        return new TwigRendererEngine();
    }
}
