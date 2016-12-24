<?php

namespace Oro\Component\PhpUtils;

class PhpIniUtil
{
    /**
     * @param  string $val
     *
     * @return int
     *
     * @see https://secure.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
     */
    public static function parseBytes($val)
    {
        if (empty($val)) {
            return 0;
        }

        preg_match('/([\-0-9]+)[\s]*([a-z]*)$/i', trim($val), $matches);

        if (isset($matches[1])) {
            $val = (int)$matches[1];
        }

        switch (strtolower($matches[2])) {
            case 'g':
            case 'gb':
                $val *= 1024;
            // no break
            case 'm':
            case 'mb':
                $val *= 1024;
            // no break
            case 'k':
            case 'kb':
                $val *= 1024;
            // no break
        }

        return (float)$val;
    }
}
