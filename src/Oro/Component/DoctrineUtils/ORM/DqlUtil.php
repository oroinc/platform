<?php

namespace Oro\Component\DoctrineUtils\ORM;

class DqlUtil
{
    /**
     * Returns TRUE if $dql contains usage of parameter with $parameterName
     *
     * @param string $dql
     * @param string $parameterName
     *
     * @return bool
     */
    public static function hasParameter($dql, $parameterName)
    {
        $pattern = is_numeric($parameterName)
            ? sprintf('/\?%s[^\w]/', preg_quote($parameterName))
            : sprintf('/\:%s[^\w]/', preg_quote($parameterName));

        return (bool) preg_match($pattern, $dql . ' ');
    }

    /**
     * @param string $dql
     *
     * @return array
     */
    public static function getAliases($dql)
    {
        $matches = [];
        preg_match_all('/(FROM|JOIN)\s+\S+\s+(AS\s+)?(\S+)/i', $dql, $matches);

        return $matches[3];
    }

    /**
     * @param string $dql
     * @param array $replacements
     *
     * @return string
     */
    public static function replaceAliases($dql, array $replacements)
    {
        return array_reduce(
            $replacements,
            function ($carry, array $replacement) {
                return preg_replace(sprintf('/(?<=[^\w\.\:])%s(?=\b)/', $replacement[0]), $replacement[1], $carry);
            },
            $dql
        );
    }

    /**
     * Builds CONCAT(...) DQL expression
     *
     * @param string[] $parts
     *
     * @return string
     */
    public static function buildConcatExpr(array $parts)
    {
        $stack = [];
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            $stack[] = count($stack) === 0
                ? $parts[$i]
                : sprintf('CONCAT(%s, %s)', $parts[$i], array_pop($stack));
        }

        if (empty($stack)) {
            return '';
        }

        return array_pop($stack);
    }
}
