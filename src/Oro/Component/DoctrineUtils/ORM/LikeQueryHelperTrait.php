<?php

namespace Oro\Component\DoctrineUtils\ORM;

/**
 * Methods for safe LIKE querying.
 */
trait LikeQueryHelperTrait
{
    /**
     * Format a value that can be used as a parameter for a DQL LIKE search.
     *
     * $qb->where("u.name LIKE (:name) ESCAPE '!'")
     *    ->setParameter('name', $this->makeLikeParam('john'))
     *
     * NOTE: You MUST manually specify the `ESCAPE '!'` in your DQL query, AND the
     * ! character MUST be wrapped in single quotes, else the Doctrine DQL
     * parser will throw an error:
     *
     * [Syntax Error] line 0, col 127: Error: Expected Doctrine\ORM\Query\Lexer::T_STRING, got '"'
     *
     * Using the $pattern argument you can change the LIKE pattern your query
     * matches again. Default is "%search%". Remember that "%%" in a sprintf
     * pattern is an escaped "%".
     *
     * Common usage:
     *
     * ->makeLikeParam('foo')         == "%foo%"
     * ->makeLikeParam('foo', '%s%%') == "foo%"
     * ->makeLikeParam('foo', '%s_')  == "foo_"
     * ->makeLikeParam('foo', '%%%s') == "%foo"
     * ->makeLikeParam('foo', '_%s')  == "_foo"
     *
     * Escapes LIKE wildcards using '!' character:
     *
     * ->makeLikeParam('foo_bar') == "%foo!_bar%"
     *
     * @param string $search        Text to search for LIKE
     * @param string $pattern       sprintf-compatible substitution pattern
     * @return string
     */
    protected function makeLikeParam($search, $pattern = '%%%s%%')
    {
        /**
         * Function defined in-line so it doesn't show up for type-hinting on
         * classes that implement this trait.
         *
         * Makes a string safe for use in an SQL LIKE search query by escaping all
         * special characters with special meaning when used in a LIKE query.
         *
         * Uses ! character as default escape character because \ character in
         * Doctrine/DQL had trouble accepting it as a single \ and instead kept
         * trying to escape it as "\\". Resulted in DQL parse errors about "Escape
         * character must be 1 character"
         *
         * % = match 0 or more characters
         * _ = match 1 character
         *
         * Examples:
         *      gloves_pink   becomes  gloves!_pink
         *      gloves%pink   becomes  gloves!%pink
         *      glo_ves%pink  becomes  glo!_ves!%pink
         *
         * @param string $search
         * @return string
         */
        $sanitizeLikeValue = function ($search) {
            $escapeChar = '!';

            $escape = [
                '\\' . $escapeChar, // Must escape the escape-character for regex
                '\%',
                '\_',
            ];
            $pattern = sprintf('/([%s])/', implode('', $escape));

            return preg_replace($pattern, $escapeChar . '$0', $search);
        };

        return sprintf($pattern, $sanitizeLikeValue($search));
    }
}
