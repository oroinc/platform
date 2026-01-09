<?php

namespace Oro\Bundle\FilterBundle\Expression\Exception;

/**
 * Thrown when a syntax error occurs in a date expression.
 *
 * This exception is raised during the parsing of date filter expressions when
 * the expression violates the expected syntax rules. It helps identify and report
 * malformed date expressions to the user or logging system.
 */
class SyntaxException extends \LogicException
{
}
