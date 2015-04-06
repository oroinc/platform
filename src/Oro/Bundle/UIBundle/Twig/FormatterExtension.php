<?php

namespace Oro\Bundle\UIBundle\Twig;

class FormatterExtension extends \Twig_Extension
{
    const EXTENSION_NAME = 'oro_formatter';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::EXTENSION_NAME;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('oro_format_filename', [$this, 'formatFilename']),
        ];
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    public function formatFilename($filename)
    {
        if (strlen($filename) > 15) {
            $filename = substr($filename, 0, 7) . '..' . substr($filename, strlen($filename) - 7);
        }

        return $filename;
    }
}
