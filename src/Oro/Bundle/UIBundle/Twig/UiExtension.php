<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\UIBundle\Twig\Parser\PlaceholderTokenParser;

class UiExtension extends \Twig_Extension
{
    /**
     * {@inheritDoc}
     */
    public function getTokenParsers()
    {
        return array(
            new PlaceholderTokenParser()
        );
    }

    public function getFilters()
    {
        return array(
            'trimString' => new \Twig_Filter_Method($this, 'trimString'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_ui';
    }

    /**
     * Trim text string
     *
     * @param string $string
     * @param int    $symbolCount
     *
     * @return string
     */
    public function trimString($string, $symbolCount = 30)
    {
        $originalLength = strlen($string);
        if ($originalLength > $symbolCount) {
            $string = substr($string, 0, $symbolCount);
            $string = sprintf('%s...', $string);
        }

        return $string;
    }
}
