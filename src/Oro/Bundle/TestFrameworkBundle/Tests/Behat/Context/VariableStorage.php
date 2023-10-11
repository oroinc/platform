<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context;

/**
 * Storage for variables that may be used by behat tests.
 */
class VariableStorage
{
    private static array $storedValues = [];

    public static function storeData(string $alias, mixed $value): void
    {
        self::$storedValues[$alias] = $value;
    }

    public static function getStoredData(string $alias): mixed
    {
        return self::$storedValues[$alias] ?? null;
    }

    public static function normalizeValue(?string $value): ?string
    {
        if (null === $value) {
            return $value;
        }

        $replacements = array_combine(
            array_map(fn ($x) => sprintf('$%s$', $x), array_keys(self::$storedValues)),
            array_values(self::$storedValues)
        );

        return strtr($value, $replacements);
    }
}
