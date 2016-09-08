<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form\RendererEngine;

use Oro\Bundle\LayoutBundle\Form\RendererEngine\TwigRendererEngine;
use Oro\Bundle\LayoutBundle\Tests\Unit\Form\RendererEngineTest;

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
