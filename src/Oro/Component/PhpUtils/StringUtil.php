<?php

namespace Oro\Component\PhpUtils;

class StringUtil
{
    /**
     * @param string[] $strings
     * @param int|null $maxChunkSize Maximum size of chunk in bytes or null if unlimited
     *
     * @return string[] Squashed $strings
     */
    public static function squashChunks(array $strings, $maxChunkSize = null)
    {
        if ($maxChunkSize === null) {
            return [implode('', $strings)];
        }

        $chunks = [];
        $stringBuffer = '';
        foreach ($strings as $string) {
            // string is bigger than maxChunkSize => send the string in the buffer
            if (strlen($string) > $maxChunkSize) {
                $chunks[] = $string;
                continue;
            }

            // sum of buffered strings + current string is bigger than maxChunkSize => send buffer in one chunk
            if ((strlen($string) + strlen($stringBuffer)) > $maxChunkSize) {
                $chunks[] = $stringBuffer;
                $stringBuffer = '';
            }

            // append current string to buffer
            $stringBuffer .= $string;
        }
        if ($stringBuffer) {
            $chunks[] = $stringBuffer;
            $stringBuffer = '';
        }

        return $chunks;
    }
}
