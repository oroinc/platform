<?php

namespace Oro\Component\PhpUtils\Formatter;

/**
 * Format values in bytes into the human-readable string
 */
class BytesFormatter
{
    /**
     * @param integer $bytes
     *
     * @return string
     */
    public static function format($bytes)
    {
        $sz = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        $key = (int)$factor;

        return isset($sz[$key]) ? sprintf("%.2f", $bytes / pow(1000, $factor)) . ' ' . $sz[$key] : $bytes;
    }
}
