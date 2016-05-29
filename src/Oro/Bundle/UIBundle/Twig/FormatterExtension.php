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

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_format_filename', [$this, 'formatFilename']),
        ];
    }

    /**
     * @param string $filename
     * @param int $cutLength
     * @param int $start
     * @param int $end
     * @return string
     */
    public function formatFilename($filename, $cutLength = 15, $start = 7, $end = 7)
    {
        $encoding = mb_detect_encoding($filename);

        if (mb_strlen($filename, $encoding) > $cutLength) {
            $filename = mb_substr($filename, 0, $start, $encoding)
                . '..'
                . mb_substr($filename, mb_strlen($filename, $encoding) - $end, null, $encoding);
        }

        return $filename;
    }
}
