<?php

namespace Oro\Bundle\ChartBundle\Exception;

/**
 * Thrown when an invalid or unsupported method is called on a chart object.
 *
 * This exception extends PHP's native {@see \BadMethodCallException} and is used to indicate
 * that a method was invoked on a chart object that either does not exist or cannot
 * be called in the current context.
 */
class BadMethodCallException extends \BadMethodCallException implements Exception
{
}
