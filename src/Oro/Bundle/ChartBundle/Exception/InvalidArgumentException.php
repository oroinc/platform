<?php

namespace Oro\Bundle\ChartBundle\Exception;

/**
 * Thrown when invalid arguments are provided to chart operations.
 *
 * This exception extends PHP's native {@see \InvalidArgumentException} and is used to indicate
 * that one or more arguments passed to a chart-related method are invalid, missing,
 * or of an unexpected type.
 */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{
}
