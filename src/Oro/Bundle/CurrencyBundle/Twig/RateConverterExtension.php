<?php

namespace Oro\Bundle\CurrencyBundle\Twig;

/**
 * Provides TWIG function for converting currencies.
 */
class RateConverterExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_multicurrency_rate_converter', [$this, 'convert']),
        ];
    }

    /**
     * This method should returned
     * formatted currency value
     * for multi currency functionality.
     * Not available for community edition
     *
     * @return string
     */
    public function convert()
    {
        return '';
    }
}
