<?php

namespace Oro\Bundle\LocaleBundle\Tools;

/**
 * Helper tools for NumberFormatter.
 */
class NumberFormatterHelper
{
    /**
     * Parse value of NumberFormatter constant or it's string name and get value
     *
     * @param int|string $name
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function parseConstantValue($name): int
    {
        if (is_int($name)) {
            return $name;
        }

        $attributeName = strtoupper($name);
        $constantName = 'NumberFormatter::' . $attributeName;
        if (!defined($constantName)) {
            throw new \InvalidArgumentException(sprintf('NumberFormatter has no constant \'%s\'', $name));
        }

        return constant($constantName);
    }
}
