<?php

namespace Oro\Bundle\SearchBundle\Exception;

/**
 * Thrown when a search query expression contains syntax errors.
 *
 * This exception is raised during parsing of search query expressions when
 * invalid syntax is encountered. It includes the error message and the cursor
 * position where the syntax error occurred to aid in debugging.
 */
class ExpressionSyntaxError extends \LogicException
{
    public function __construct($message, $cursor = 0)
    {
        parent::__construct(sprintf('%s around position %d.', $message, $cursor));
    }
}
