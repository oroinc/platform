<?php

namespace Oro\Bundle\EntityBundle\Exception\Fallback;

/**
 * Thrown when an invalid argument is passed to a fallback provider method.
 *
 * This exception indicates that the argument provided to a fallback provider
 * is not valid or does not meet the provider's requirements.
 */
class InvalidFallbackProviderArgumentException extends \Exception
{
}
