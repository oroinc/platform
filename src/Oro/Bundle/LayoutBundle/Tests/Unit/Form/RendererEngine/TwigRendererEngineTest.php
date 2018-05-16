<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Form\RendererEngine;

use Oro\Bundle\LayoutBundle\Form\RendererEngine\TwigRendererEngine;
use Oro\Bundle\LayoutBundle\Tests\Unit\Form\RendererEngineTest;
use Twig\Environment;

class TwigRendererEngineTest extends RendererEngineTest
{
    /**
     * {@inheritdoc}
     */
    public function createRendererEngine()
    {
        /** @var Environment|\PHPUnit_Framework_MockObject_MockObject $environment */
        $environment = $this->createMock(Environment::class);

        return new TwigRendererEngine([], $environment);
    }
}
