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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_ui';
    }
}
