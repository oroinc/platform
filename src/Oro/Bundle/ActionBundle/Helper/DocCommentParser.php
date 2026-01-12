<?php

namespace Oro\Bundle\ActionBundle\Helper;

/**
 * Extracts and filters PHPDoc comments from class definitions.
 *
 * This utility class provides methods to retrieve and parse PHPDoc comments from
 * class definitions, with support for extracting full comments or just the brief
 * description, while filtering out package and suppression annotations.
 */
class DocCommentParser
{
    /**
     * @param string $className
     *
     * @return string
     */
    public function getFullComment($className)
    {
        $reflection = new \ReflectionClass($className);

        return $this->filterComment($reflection->getDocComment());
    }

    /**
     * Returns only first block from full comment
     *
     * @param string $className
     *
     * @return string
     */
    public function getShortComment($className)
    {
        // remove lines after the latest empty string
        return trim(preg_replace('#\n\s*\n.+$#s', '', $this->getFullComment($className)));
    }

    /**
     * @param string $comment
     *
     * @return string
     */
    private function filterComment($comment)
    {
        $patterns = [
            '#/?\*+.?#',
            '#^\s*@(package|SuppressWarning).+$#mi',
        ];
        foreach ($patterns as $pattern) {
            $comment = trim(preg_replace($pattern, '', $comment));
        }

        return $comment;
    }
}
