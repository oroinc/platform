<?php

namespace Oro\Bundle\LayoutBundle\Twig;

use Oro\Bundle\LayoutBundle\Form\TwigRendererEngineInterface;
use Oro\Bundle\LayoutBundle\Form\TwigRendererInterface;
use Oro\Component\Layout\Renderer;
use Twig\Environment;

/**
 * Heavily inspired by TwigRenderer class
 *
 * @see \Symfony\Bridge\Twig\Form\TwigRenderer
 */
class TwigRenderer extends Renderer implements TwigRendererInterface
{
    /**
     * @var TwigRendererEngineInterface
     */
    protected $engine;

    public function __construct(TwigRendererEngineInterface $engine)
    {
        parent::__construct($engine);
    }

    /**
     * {@inheritdoc}
     */
    public function setEnvironment(Environment $environment)
    {
        $this->engine->setEnvironment($environment);
    }
}
