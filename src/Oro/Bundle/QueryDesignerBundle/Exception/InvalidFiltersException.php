<?php

namespace Oro\Bundle\QueryDesignerBundle\Exception;

/**
 * Thrown when the filter structure or syntax is invalid.
 *
 * This exception is raised during filter parsing when the filter definition
 * contains syntax errors, invalid operators, or violates the expected filter
 * structure rules (e.g., mismatched groups, unexpected operators).
 */
class InvalidFiltersException extends \RuntimeException
{
}
