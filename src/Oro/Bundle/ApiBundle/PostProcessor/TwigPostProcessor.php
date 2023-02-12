<?php

namespace Oro\Bundle\ApiBundle\PostProcessor;

use Twig\Environment;

/**
 * Applies a TWIG template to a field value.
 */
class TwigPostProcessor implements PostProcessorInterface
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * {@inheritDoc}
     */
    public function process(mixed $value, array $options): mixed
    {
        if (null === $value) {
            return null;
        }

        $twigContent = $options;
        $twigContent['value'] = $value;
        unset($twigContent['template']);

        return $this->twig->render($options['template'], $twigContent);
    }
}
