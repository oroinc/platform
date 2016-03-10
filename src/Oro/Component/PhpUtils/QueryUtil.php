<?php

namespace Oro\Component\PhpUtils;

class QueryUtil
{
    const IN = 'in';
    const IN_BETWEEN = 'in_between';

    /**
     * @param int[] $intValues Values usually passed to IN()
     *
     * @return array
     */
    public static function optimizeIntValues(array $intValues)
    {
        $values = ArrayUtil::intRanges($intValues);

        $result = [
            static::IN => [],
            static::IN_BETWEEN => [],
        ];

        foreach ($values as $value) {
            list($min, $max) = $value;
            if ($min === $max) {
                $result[static::IN][] = $min;
            } else {
                $result[static::IN_BETWEEN][] = $value;
            }
        }

        // when there is lots of ranges, it takes way longer than IN
        if (count($result[static::IN_BETWEEN]) > 1000) {
            $result[static::IN] = $intValues;
            $result[static::IN_BETWEEN] = [];
        }

        return $result;
    }

    /**
     * @param string $prefix
     */
    public static function generateParameterName($prefix)
    {
        static $n = 0;
        $n++;

        return sprintf('%s_%d', uniqid($prefix), $n);
    }
}
