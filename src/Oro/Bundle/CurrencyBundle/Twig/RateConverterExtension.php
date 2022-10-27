<?php

namespace Oro\Bundle\CurrencyBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to retrieve base currency value:
 *   - oro_multicurrency_rate_converter
 *
 * This function is not available in the community edition.
 */
class RateConverterExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_multicurrency_rate_converter', [$this, 'convert']),
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
