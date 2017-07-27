<?php

namespace Oro\Bundle\CurrencyBundle\Twig;

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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_multicurrency';
    }
}
