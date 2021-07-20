<?php

namespace Oro\Bundle\ApiBundle\PostProcessor;

use Twig\Environment;

/**
 * Applies a TWIG template to a field value.
 */
class TwigPostProcessor implements PostProcessorInterface
{
    /** @var Environment */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * {@inheritDoc}
     */
    public function process($value, array $options)
    {
        if (null === $value) {
            return $value;
        }

        $twigContent = $options;
        $twigContent['value'] = $value;
        unset($twigContent['template']);

        return $this->twig->render($options['template'], $twigContent);
    }
}
