<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form\RendererEngine;

use Oro\Bundle\LayoutBundle\Form\RendererEngine\TemplatingRendererEngine;

class TemplatingRendererEngineTest extends RendererEngineTest
{
    /**
     * {@inheritdoc}
     */
    public function createRendererEngine()
    {
        /** @var \Symfony\Component\Templating\EngineInterface $templatingEngine */
        $templatingEngine = $this->getMock('Symfony\Component\Templating\EngineInterface');

        return new TemplatingRendererEngine($templatingEngine);
    }
}
