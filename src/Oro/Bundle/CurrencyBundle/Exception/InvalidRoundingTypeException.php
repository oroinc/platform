<?php

namespace Oro\Bundle\CurrencyBundle\Exception;

/**
 * Thrown when an invalid or unsupported rounding type is specified.
 *
 * This exception is raised when attempting to use a rounding type that is not
 * recognized by the rounding service, such as an invalid constant or configuration
 * value for monetary value rounding operations.
 */
class InvalidRoundingTypeException extends \Exception
{
}
